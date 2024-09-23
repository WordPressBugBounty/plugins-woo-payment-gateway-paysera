<?php

declare(strict_types=1);

namespace Paysera\EventHandler;

use Paysera\DeliveryApi\MerchantClient\Entity\Order;
use Paysera\DeliveryApi\MerchantClient\MerchantClient;
use Paysera\Dto\DeliveryTerminalLocationDto;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\Helper\PayseraDeliveryHelper;
use Paysera\Helper\PayseraDeliveryLibraryHelper;
use Paysera\Helper\WCOrderFieldUpdateHelper;
use Paysera\Helper\WCOrderMetaUpdateHelper;
use Paysera\Helper\WCOrderUpdateHelperInterface;
use Paysera\Service\CompatibilityManager;
use Paysera\Service\LoggerInterface;
use Paysera_Delivery_Gateway;
use WC_Order;
use WC_Order_Item_Shipping;

class DeliveryOrderUpdatedHandler implements EventHandlerInterface
{
    private const RECEIVER_DATA_TEMPLATES = [
        'billing_email' => 'Billing email: %s',
        'shipping_phone' => 'Shipping phone: %s',
        'shipping_address_1' => 'Street %s',
        'shipping_postcode' => 'Postal code: %s',
        'shipping_country' => 'Country: %s',
        'shipping_city' => 'City: %s',
        'house_number' => 'House number: %s',
    ];

    private LoggerInterface $logger;
    private ?MerchantClient $merchantClient;
    private PayseraDeliveryHelper $deliveryHelper;
    private PayseraDeliveryLibraryHelper $deliveryLibraryHelper;
    private WCOrderMetaUpdateHelper $metaUpdateHelper;
    private WCOrderFieldUpdateHelper $fieldUpdateHelper;
    private CompatibilityManager $compatibilityManager;

    public function __construct(
        PayseraDeliveryHelper $deliveryHelper,
        PayseraDeliveryLibraryHelper $deliveryLibraryHelper,
        LoggerInterface $logger,
        WCOrderMetaUpdateHelper $metaUpdateHelper,
        WCOrderFieldUpdateHelper $fieldUpdateHelper
    ) {
        $this->logger = $logger;
        $this->deliveryHelper = $deliveryHelper;
        $this->deliveryLibraryHelper = $deliveryLibraryHelper;
        $this->merchantClient = $this->deliveryHelper->getMerchantDeliveryClient();
        $this->metaUpdateHelper = $metaUpdateHelper;
        $this->fieldUpdateHelper = $fieldUpdateHelper;
        $this->compatibilityManager = new CompatibilityManager();
    }

    /**
     * @inheritDoc
     */
    public function handle(array $payload): void
    {
        $orderId = (int)($payload['orderId'] ?? 0);

        if ($orderId === 0 || !$this->merchantClient) {
            return;
        }

        /** @var WC_Order $order */
        $order = wc_get_order($orderId);

        if (!$order || !$order->meta_exists(PayseraDeliverySettings::DELIVERY_ORDER_ID_META_KEY)) {
            return;
        }

        $this->handleOrder($order);
    }

    public function handleOrder(WC_Order $order): WC_Order
    {
        $deliveryOrder = $this->merchantClient->getOrder(
            $order->get_meta(PayseraDeliverySettings::DELIVERY_ORDER_ID_META_KEY)
        );

        $this->updateRecipient($order, $deliveryOrder);
        $this->updateDeliveryGateway($order, $deliveryOrder);

        return $order;
    }

    private function updateRecipient(WC_Order $order, Order $deliveryOrder): void
    {
        $receiver = $deliveryOrder->getReceiver();

        if ($receiver === null) {
            return;
        }

        $contact = $receiver->getContact();

        if ($contact === null) {
            return;
        }

        $contactShippingInfo = $contact->getParty();
        $shippingAddress = $contact->getAddress();

        $previousRecipientData = [
            'billing_email' => $order->get_billing_email(),
            'shipping_phone' => $this->compatibilityManager->Order($order)->getShippingPhone(),
            'shipping_country' => $order->get_shipping_country(),
            'shipping_city' => $order->get_shipping_city(),
            'shipping_address_1' => $order->get_shipping_address_1(),
            'shipping_postcode' => $order->get_shipping_postcode(),
        ];

        $actualRecipientData = [
            'billing_email' => (string)$contactShippingInfo->getEmail(),
            'shipping_phone' => (string)$contactShippingInfo->getPhone(),
            'shipping_country' => (string)$shippingAddress->getCountry(),
            'shipping_city' => (string)$shippingAddress->getCity(),
            'shipping_address_1' => (string)$shippingAddress->getStreet(),
            'shipping_postcode' => (string)$shippingAddress->getPostalCode(),
        ];

        $isOrderFieldsUpdated = $this->updateOrderData($order, $actualRecipientData, $this->fieldUpdateHelper);

        $houseNumber = $shippingAddress->getHouseNumber();
        $houseNumberKey = PayseraDeliverySettings::ORDER_META_KEY_HOUSE_NO;

        if (
            $order->meta_exists($houseNumberKey)
            && $houseNumber !== null
            && $this->metaUpdateHelper->canUpdate($order, $houseNumberKey, $houseNumber)
        ) {
            $previousRecipientData['house_number'] = $order->get_meta(PayseraDeliverySettings::ORDER_META_KEY_HOUSE_NO);
            $actualRecipientData['house_number'] = $houseNumber;
            $this->metaUpdateHelper->handleUpdate($order, $houseNumberKey, $houseNumber);
            $isOrderFieldsUpdated = true;
        }

        if (!$isOrderFieldsUpdated) {
           return;
        }

        [$previousRecipientData, $actualRecipientData] = $this->extractRecipientDataChanges(
            $previousRecipientData,
            $actualRecipientData
        );

        $this->logRecipientChanges($order->get_id(), $previousRecipientData, $actualRecipientData);

        $order->add_order_note(
            __(
                PayseraPaths::PAYSERA_MESSAGE . 'Receiver\'s data has been changed',
                PayseraPaths::PAYSERA_TRANSLATIONS
            )
        );

        $order->save();
    }

    private function extractRecipientDataChanges(array $previousData, array $currentData): array
    {
        $changedData = array_diff_assoc($currentData, $previousData);
        $changedFields = array_keys($changedData);
        $changedDataPreviousValues = array_filter(
            $previousData,
            fn(string $key): bool => in_array($key, $changedFields, true),
            ARRAY_FILTER_USE_KEY
        );

        return [
            $changedDataPreviousValues,
            $changedData,
        ];
    }

    private function logRecipientChanges(int $orderId, array $previousData, array $currentData): void
    {
        $this->logger->info(
            sprintf(
                implode(
                    "\n",
                    [
                        'Receiver data changed for order id %d.',
                        'Previous: %s',
                        'Current: %s',
                    ]
                ),
                $orderId,
                $this->prepareRecipientDataInfoString($previousData),
                $this->prepareRecipientDataInfoString($currentData),
            )
        );
    }

    private function prepareRecipientDataInfoString(array $recipientData): string
    {
        $newline = "\n\t";
        $messageData = [];

        foreach ($recipientData as $field => $value) {
            $messageData[] = isset(self::RECEIVER_DATA_TEMPLATES[$field])
                ? sprintf(self::RECEIVER_DATA_TEMPLATES[$field], (string)$value)
                : $field . ':' . $value;
        }

        return $newline . implode($newline, $messageData);
    }

    private function updateDeliveryGateway(WC_Order $order, Order $deliveryOrder): void
    {
        $deliveryGateway = $this->deliveryHelper->getMatchedActiveGatewayForDeliveryOrder($deliveryOrder);

        if ($deliveryGateway === null) {
            $this->logger->error(
                sprintf(
                    'Cannot find delivery gateway with code \'%s\' for order %s.',
                    $this->deliveryHelper->getGatewayCodeFromDeliveryOrder($deliveryOrder),
                    $order->get_id(),
                )
            );

            $order->add_order_note(
                sprintf(
                    __(
                        PayseraPaths::PAYSERA_MESSAGE
                        . 'Shipping method of delivery order %s has been changed, '
                        . 'but it doesn\'t match with any active shipping method.',
                        PayseraPaths::PAYSERA_TRANSLATIONS
                    ),
                    $order->get_id(),
                )
            );

            return;
        }

        $actualDeliveryGateway = $this->deliveryHelper->getPayseraShippingFromOrder($order);

        if ($actualDeliveryGateway === null) {
            return;
        }

        $oldDeliveryGatewayCode = $actualDeliveryGateway->get_method_id();

        if ($actualDeliveryGateway->get_method_id() !== $deliveryGateway->id) {
            $this->changeDeliveryGateway($order, $actualDeliveryGateway, $deliveryGateway);
        }

        $shippingMethod = $deliveryOrder->getShipmentMethod();

        if (
            $shippingMethod !== null
            && $shippingMethod->getReceiverCode() === PayseraDeliverySettings::TYPE_PARCEL_MACHINE
        ) {
            $this->updateParcelMachine(
                $order,
                $deliveryOrder,
                $deliveryGateway->id,
                $oldDeliveryGatewayCode,
            );
        } else {
            $this->deleteParcelMachine($order);
        }
    }

    private function changeDeliveryGateway(
        WC_Order $order,
        WC_Order_Item_Shipping $actualDeliveryGateway,
        Paysera_Delivery_Gateway $deliveryGateway
    ): void {
        $calculateTaxFor = [
            'country'  => $order->get_shipping_country(),
            'state'    => $order->get_shipping_state(),
            'postcode' => $order->get_shipping_postcode(),
            'city'     => $order->get_shipping_city(),
        ];

        $deliveryGatewayCode = $actualDeliveryGateway->get_method_id();

        $actualDeliveryGateway->set_method_id($deliveryGateway->id);
        $actualDeliveryGateway->set_name($deliveryGateway->instance_settings['title']);
        $actualDeliveryGateway->set_total($deliveryGateway->instance_settings['fee']);
        $actualDeliveryGateway->calculate_taxes($calculateTaxFor);
        $actualDeliveryGateway->save();

        $order->calculate_totals();
        $order->save();

        $order->add_order_note(
            sprintf(
                __(
                    PayseraPaths::PAYSERA_MESSAGE . 'Shipping method has been changed to %s',
                    PayseraPaths::PAYSERA_TRANSLATIONS
                ),
                $deliveryGateway->instance_settings['title']
            )
        );

        $this->logger->info(
            sprintf(
                'Delivery gateway for order %d has been changed from %s to %s.',
                $order->get_id(),
                $deliveryGatewayCode,
                $deliveryGateway->id,
            )
        );
    }

    private function updateParcelMachine(
        WC_Order $order,
        Order $deliveryOrder,
        string $newDeliveryGatewayCode,
        string $oldDeliveryGatewayCode
    ): void {
        $parcelMachine = $deliveryOrder->getReceiver()->getParcelMachine();

        if ($parcelMachine === null) {
            return;
        }

        $address = $parcelMachine->getAddress();
        $terminalId = $parcelMachine->getId();

        $wcOrderMetaData = [
            PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_COUNTRY_META_KEY => $address->getCountry(),
            PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_CITY_META_KEY => $address->getCity(),
            PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_KEY => $terminalId,
        ];

        $oldTerminalLocation = $this->getOldTerminalLocation($order, $oldDeliveryGatewayCode);

        if (!$this->updateOrderData($order, $wcOrderMetaData, $this->metaUpdateHelper)) {
            return;
        }

        $newTerminalLocation = new DeliveryTerminalLocationDto(
            $address->getCountry(),
            $address->getCity(),
            $this->deliveryHelper->resolveDeliveryGatewayCode($newDeliveryGatewayCode),
            $terminalId,
        );

        $order->add_order_note(
            $this->deliveryLibraryHelper->formatChangedTerminalNote($newTerminalLocation)
        );

        $this->logger->info(
            $this->deliveryLibraryHelper->formatChangedTerminalLogMsg($newTerminalLocation, $oldTerminalLocation)
        );

        $order->save();
    }

    private function getOldTerminalLocation(
        WC_Order $order,
        string $oldDeliveryGatewayCode
    ): ?DeliveryTerminalLocationDto {
        $countryCode = $order->get_meta(PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_COUNTRY_META_KEY);
        $city = $order->get_meta(PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_CITY_META_KEY);
        $terminalId = $order->get_meta(PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_KEY);

        if (empty($countryCode) || empty($city) || empty($terminalId)) {
            return null;
        }

        return new DeliveryTerminalLocationDto(
            (string)$countryCode,
            (string)$city,
            $this->deliveryHelper->resolveDeliveryGatewayCode($oldDeliveryGatewayCode),
            (string)$terminalId,
        );
    }

    private function updateOrderData(WC_Order $order, array $actualData, WCOrderUpdateHelperInterface $context): bool
    {
        $isRecipientDataUpdated = false;

        foreach ($actualData as $targetField => $actualValue) {
            if (!$context->canUpdate($order, $targetField, $actualValue)) {
                continue;
            }

            $context->handleUpdate($order, $targetField, $actualValue);
            $isRecipientDataUpdated = true;
        }

        return $isRecipientDataUpdated;
    }

    private function deleteParcelMachine(WC_Order $order): void
    {
        $metaKeys = [
            PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_KEY,
            PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_CITY_META_KEY,
            PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_COUNTRY_META_KEY,
        ];

        foreach ($metaKeys as $metaKey) {
            $order->delete_meta_data($metaKey);
        }

        $order->save();
    }
}

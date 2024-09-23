<?php

declare(strict_types=1);

namespace Paysera\Helper;

use Paysera\DeliveryApi\MerchantClient\Entity\Address;
use Paysera\DeliveryApi\MerchantClient\Entity\Contact;
use Paysera\DeliveryApi\MerchantClient\Entity\OrderCreate;
use Paysera\DeliveryApi\MerchantClient\Entity\OrderUpdate;
use Paysera\DeliveryApi\MerchantClient\Entity\Party;
use Paysera\DeliveryApi\MerchantClient\Entity\ShipmentCreate;
use Paysera\DeliveryApi\MerchantClient\Entity\ShipmentPointCreate;
use Paysera\Dto\DeliveryTerminalLocationDto;
use Paysera\Entity\PayseraDeliveryGatewaySettings;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Service\CompatibilityManager;
use Paysera\Service\LoggerInterface;
use WC_Order;

class PayseraDeliveryOrderHelper
{
    private const SHIPPING_METHOD_CODE_TEMPLATE = '%s2%s';

    private PayseraDeliverySettingsProvider $payseraDeliverySettingsProvider;
    private PayseraDeliveryLibraryHelper $payseraDeliveryLibraryHelper;
    private PayseraDeliveryHelper $payseraDeliveryHelper;
    private LoggerInterface $logger;
    private SessionHelperInterface $sessionHelper;
    private CallbackHelper $callbackHelper;
    private CompatibilityManager $compatibilityManager;

    public function __construct(
        PayseraDeliverySettingsProvider $payseraDeliverySettingsProvider,
        PayseraDeliveryLibraryHelper $payseraDeliveryLibraryHelper,
        PayseraDeliveryHelper $payseraDeliveryHelper,
        LoggerInterface $logger,
        SessionHelperInterface $sessionHelper,
        CallbackHelper $callbackHelper
    ) {
        $this->payseraDeliverySettingsProvider = $payseraDeliverySettingsProvider;
        $this->payseraDeliveryLibraryHelper = $payseraDeliveryLibraryHelper;
        $this->payseraDeliveryHelper = $payseraDeliveryHelper;
        $this->logger = $logger;
        $this->sessionHelper = $sessionHelper;
        $this->callbackHelper = $callbackHelper;
        $this->compatibilityManager = new CompatibilityManager();
    }

    public function getOrderCreate(
        WC_Order $order,
        string $deliveryGatewayCode,
        int $deliveryGatewayInstanceId,
        ?string $projectId
    ): OrderCreate {
        $deliveryGatewaySettings = $this->payseraDeliverySettingsProvider->getPayseraDeliveryGatewaySettings(
            $deliveryGatewayCode,
            $deliveryGatewayInstanceId
        );

        $deliveryOrder = (new OrderCreate())
            ->setShipmentGatewayCode($this->payseraDeliveryHelper->resolveDeliveryGatewayCode($deliveryGatewayCode))
            ->setShipmentMethodCode($this->getShipmentMethodCode($deliveryGatewaySettings))
            ->setShipments($this->getShipments($order))
            ->setReceiver($this->getReceiversShipmentPoint($order, $deliveryGatewayCode, $deliveryGatewaySettings))
            ->setEshopOrderId((string)$order->get_id())
            ->setOrderNotification($this->callbackHelper->configureOrderNotificationCallback($order->get_id()))
        ;

        if ($projectId !== null) {
            $deliveryOrder->setProjectId($projectId);
        }

        return $deliveryOrder;
    }

    public function getOrderUpdate(
        WC_Order $order,
        string $deliveryGatewayCode,
        int $deliveryGatewayInstanceId,
        ?string $projectId
    ): OrderUpdate {
        $deliveryGatewaySettings = $this->payseraDeliverySettingsProvider->getPayseraDeliveryGatewaySettings(
            $deliveryGatewayCode,
            $deliveryGatewayInstanceId
        );

        $deliveryOrder = (new OrderUpdate())
            ->setShipmentGatewayCode($this->payseraDeliveryHelper->resolveDeliveryGatewayCode($deliveryGatewayCode))
            ->setShipmentMethodCode($this->getShipmentMethodCode($deliveryGatewaySettings))
            ->setShipments($this->getShipments($order))
            ->setReceiver($this->getReceiversShipmentPoint($order, $deliveryGatewayCode, $deliveryGatewaySettings))
            ->setEshopOrderId((string)$order->get_id())
            ->setOrderNotification($this->callbackHelper->configureOrderNotificationCallback($order->get_id()))
        ;

        if ($projectId !== null) {
            $deliveryOrder->setProjectId($projectId);
        }

        return $deliveryOrder;
    }

    private function getShipmentMethodCode(PayseraDeliveryGatewaySettings $deliveryGatewaySettings): string
    {
        return sprintf(
            self::SHIPPING_METHOD_CODE_TEMPLATE,
            $deliveryGatewaySettings->getSenderType(),
            $deliveryGatewaySettings->getReceiverType()
        );
    }

    /**
     * @param WC_Order $order
     * @return array<ShipmentCreate>
     */
    private function getShipments(WC_Order $order): array
    {
        $orderId = $order->get_id();
        $shipments = [];

        foreach ($order->get_items() as $item) {
            $itemData = $item->get_data();
            $product = wc_get_product($itemData['product_id']);

            for ($productQuantity = 1; $productQuantity <= $itemData['quantity']; $productQuantity++) {
                $shipment = $this->payseraDeliveryLibraryHelper->createShipment($product);

                $this->logger->info(
                    sprintf(
                        'Shipment product dimensions for order id %d: Weight: %s, Length: %s, Width: %s, Height: %s.',
                        $orderId,
                        $shipment->getWeight(),
                        $shipment->getLength(),
                        $shipment->getWidth(),
                        $shipment->getHeight()
                    )
                );

                $shipments[] = $shipment;
            }
        }

        return $shipments;
    }

    private function getReceiversShipmentPoint(
        WC_Order $order,
        string $deliveryGatewayCode,
        PayseraDeliveryGatewaySettings $deliveryGatewaySettings
    ): ShipmentPointCreate {
        $receiversAddress = $this->getReceiversAddress($order);
        $parcelMachineId = $this->assignReceiversParcelMachine(
            $receiversAddress,
            $order,
            $deliveryGatewayCode,
            $deliveryGatewaySettings
        );
        $contact = $this->getReceiversContact($order, $receiversAddress);

        return $this->payseraDeliveryLibraryHelper->createOrderParty(
            $deliveryGatewaySettings->getReceiverType(),
            ShipmentPointCreate::TYPE_RECEIVER,
            $contact,
            $parcelMachineId
        );
    }

    public function getReceiversAddress(WC_Order $order): Address
    {
        $receiverAddress = (new Address())
            ->setCountry($order->get_shipping_country())
            ->setState($order->get_shipping_state())
            ->setCity($order->get_shipping_city())
            ->setStreet($order->get_shipping_address_1())
            ->setPostalCode($order->get_shipping_postcode())
        ;

        if ($this->sessionHelper->getData(PayseraDeliverySettings::SHIPPING_HOUSE_NO) !== '') {
            $receiverAddress->setHouseNumber($this->sessionHelper->getData(PayseraDeliverySettings::SHIPPING_HOUSE_NO));
        } elseif ($this->sessionHelper->getData(PayseraDeliverySettings::BILLING_HOUSE_NO) !== '') {
            $receiverAddress->setHouseNumber($this->sessionHelper->getData(PayseraDeliverySettings::BILLING_HOUSE_NO));
        }

        return $receiverAddress;
    }

    private function getReceiversContact(WC_Order $order, Address $receiverAddress): Contact
    {
        $receiverTitle = array_filter(
            [
                $order->get_billing_first_name(),
                $order->get_billing_last_name(),
                $order->get_billing_company(),
            ],
            fn($item) => (string)$item !== ''
        );

        $receiverParty = (new Party())
            ->setTitle(implode(' ', $receiverTitle))
            ->setEmail($order->get_billing_email())
            ->setPhone($this->compatibilityManager->Order($order)->getShippingPhone())
        ;

        return (new Contact())
            ->setParty($receiverParty)
            ->setAddress($receiverAddress)
        ;
    }

    private function assignReceiversParcelMachine(
        Address $receiverAddress,
        WC_Order $order,
        string $deliveryGatewayCode,
        PayseraDeliveryGatewaySettings $deliveryGatewaySettings
    ): ?string {
        $parcelMachineId = null;

        if ($deliveryGatewaySettings->getReceiverType() === PayseraDeliverySettings::TYPE_PARCEL_MACHINE) {
            $terminalCountry = $this->sessionHelper->getData(PayseraDeliverySettings::TERMINAL_COUNTRY);
            $terminalCity = $this->sessionHelper->getData(PayseraDeliverySettings::TERMINAL_CITY);
            $parcelMachineId = $this->sessionHelper->getData(PayseraDeliverySettings::TERMINAL);

            $this->logger->info(sprintf(
                'Terminal session data for order id %d. Country: %s, City: %s, Terminal: %s',
                $order->get_id(),
                $terminalCountry,
                $terminalCity,
                $parcelMachineId,
            ));

            $receiverAddress->setCountry($terminalCountry);
            $receiverAddress->setCity($terminalCity);

            $order->add_order_note(
                $this->payseraDeliveryLibraryHelper->formatSelectedTerminalNote(
                    new DeliveryTerminalLocationDto(
                        $terminalCountry,
                        $terminalCity,
                        $this->payseraDeliveryHelper->resolveDeliveryGatewayCode($deliveryGatewayCode),
                        $parcelMachineId,
                    )
                )
            );

            $order->update_meta_data(PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_COUNTRY_META_KEY, $terminalCountry);
            $order->update_meta_data(PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_CITY_META_KEY, $terminalCity);
            $order->update_meta_data(PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_KEY, $parcelMachineId);
            $order->save_meta_data();
        }

        return $parcelMachineId;
    }
}

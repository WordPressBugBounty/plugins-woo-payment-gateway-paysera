<?php

declare(strict_types=1);

namespace Paysera\Entity\Delivery;

use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Factory\PartyFactory;
use Paysera\Scoped\Paysera\DeliverySdk\Collection\OrderItemsCollection;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderPartyInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\NotificationCallbackInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliveryGatewayInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliverySettingsInterface;
use WC_Order;
use WC_Order_Item_Shipping;

class Order extends AbstractEntity implements MerchantOrderInterface
{
    private WC_Order $order;
    private int $id;
    private Party $shipping;
    private Party $billing;
    private OrderItemsCollection $orderItems;
    private Callback $callback;
    private ?WC_Order_Item_Shipping $actualShippingMethod = null;
    private PartyFactory $partyFactory;
    private bool $testMode;

    public function __construct(
        WC_Order $order,
        OrderItemsCollection $orderItems,
        PartyFactory $partyFactory
    ) {
        $this->order = $order;
        $this->id = $order->get_id();
        $this->initActualShippingMethod();
        $this->partyFactory = $partyFactory;
        $this->initParties();
        $this->orderItems = $orderItems;
        $this->callback = new Callback($this->id);
        $this->testMode = (bool) $order->get_meta(PayseraDeliverySettings::DELIVERY_TEST_MODE) ?? false;
    }

    public function setTestMode(bool $testMode): MerchantOrderInterface
    {
        $this->testMode = $testMode;
        return $this;
    }

    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    public function getWcOrder(): WC_Order
    {
        return $this->order;
    }

    public function getNumber(): string
    {
        return (string)$this->id;
    }

    public function getDeliveryOrderId(): ?string
    {
        return $this->order->get_meta(PayseraDeliverySettings::DELIVERY_ORDER_ID_META_KEY);
    }

    public function getDeliveryOrderNumber(): ?string
    {
        return $this->order->get_meta(PayseraDeliverySettings::DELIVERY_ORDER_NUMBER_META_KEY);
    }

    public function setDeliveryOrderId(string $id): MerchantOrderInterface
    {
        $this->order->update_meta_data(PayseraDeliverySettings::DELIVERY_ORDER_ID_META_KEY, $id);

        return $this;
    }

    public function setDeliveryOrderNumber(?string $number): MerchantOrderInterface
    {
        $this->order->update_meta_data(PayseraDeliverySettings::DELIVERY_ORDER_NUMBER_META_KEY, $number);

        return $this;
    }

    public function getShipping(): MerchantOrderPartyInterface
    {
        return $this->shipping;
    }

    public function getBilling(): ?MerchantOrderPartyInterface
    {
        return $this->billing;
    }

    /**
     * @inheritDoc
     */
    public function getItems(): OrderItemsCollection
    {
        return $this->orderItems;
    }

    public function getNotificationCallback(): ?NotificationCallbackInterface
    {
        return $this->callback;
    }

    public function getActualShippingMethod(): ?WC_Order_Item_Shipping
    {
        return $this->actualShippingMethod;
    }

    public function getDeliveryGateway(): ?PayseraDeliveryGatewayInterface
    {
        if ($this->actualShippingMethod === null) {
            return null;
        }

        $deliveryGatewayCode = $this->actualShippingMethod->get_method_id();
        $deliveryGatewayInstanceId = (int)$this->actualShippingMethod->get_instance_id();

        $className = sprintf(
            '%s_Delivery',
            ucwords(str_replace('_delivery', '', $deliveryGatewayCode), '_')
        );

        return class_exists($className)
            ? new $className($deliveryGatewayInstanceId)
            : null;
    }

    public function setDeliveryGateway(PayseraDeliveryGatewayInterface $deliveryGateway): MerchantOrderInterface
    {
        $calculateTaxFor = [
            'country'  => $this->order->get_shipping_country(),
            'state'    => $this->order->get_shipping_state(),
            'postcode' => $this->order->get_shipping_postcode(),
            'city'     => $this->order->get_shipping_city(),
        ];

        $this->actualShippingMethod->set_method_id($deliveryGateway->id);
        $this->actualShippingMethod->set_name($deliveryGateway->getName());
        $this->actualShippingMethod->set_total($deliveryGateway->getFee());
        $this->actualShippingMethod->calculate_taxes($calculateTaxFor);

        return $this;
    }

    private function initActualShippingMethod(): void
    {
        foreach ($this->order->get_items('shipping') as $shippingItem) {
            if (
                strpos(
                    $shippingItem->get_method_id(),
                    PayseraDeliverySettingsInterface::DELIVERY_GATEWAY_PREFIX
                ) !== false
            ) {
                $this->actualShippingMethod = $shippingItem;

                break;
            }
        }
    }

    private function initParties(): void
    {
        $this->shipping = $this->partyFactory->createShipping($this);
        $this->billing = $this->partyFactory->createBilling($this);
    }
}

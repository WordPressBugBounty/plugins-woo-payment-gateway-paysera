<?php

declare(strict_types=1);

namespace Paysera\Entity\Delivery;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\DeliveryTerminalLocationFactoryInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\DeliveryTerminalLocationInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderAddressInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderContactInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderPartyInterface;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Scoped\Psr\Container\ContainerInterface;
use WC_Order;
use WC_Order_Item_Shipping;

class Party extends AbstractEntity implements MerchantOrderPartyInterface
{
    public const TYPE_SHIPPING = 'shipping';
    public const TYPE_BILLING = 'billing';

    private const META_MAP = [
        PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_COUNTRY_META_KEY => 'getCountry',
        PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_CITY_META_KEY => 'getCity',
        PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_KEY => 'getTerminalId',
    ];

    private WC_Order $order;
    private Contact $contact;
    private Address $address;
    private string $deliveryGatewayCode;
    private ContainerInterface $container;

    public function __construct(
        WC_Order $wcOrder,
        string $type,
        string $deliveryGatewayCode,
        ContainerInterface $container
    ) {
        $this->container = $container;
        $this->order = $wcOrder;
        $this->contact = new Contact($this->order, $type, $this->container);
        $this->address = new Address($this->order, $type);
        $this->deliveryGatewayCode = $deliveryGatewayCode;
    }

    public function getContact(): MerchantOrderContactInterface
    {
        return $this->contact;
    }

    public function getAddress(): MerchantOrderAddressInterface
    {
        return $this->address;
    }

    public function setTerminalLocation(?DeliveryTerminalLocationInterface $terminalLocation): MerchantOrderPartyInterface
    {
        if ($terminalLocation === null) {
            foreach (array_keys(self::META_MAP) as $metaKey) {
                if ($this->order->meta_exists()) {
                    $this->order->delete_meta_data($metaKey);
                }
            }

            return $this;
        }

        foreach (self::META_MAP as $metaKey => $getterMethod) {
            $this->order->update_meta_data($metaKey, $terminalLocation->{$getterMethod}());
        }

        return $this;
    }

    public function getTerminalLocation(): ?DeliveryTerminalLocationInterface
    {
        $countryCode = $this->order->get_meta(PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_COUNTRY_META_KEY);
        $city = $this->order->get_meta(PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_CITY_META_KEY);
        $terminalId = $this->order->get_meta(PayseraDeliverySettings::DELIVERY_ORDER_TERMINAL_KEY);

        if (empty($countryCode) || empty($city) || empty($terminalId)) {
            return null;
        }

        return $this->container
            ->get(DeliveryTerminalLocationFactoryInterface::class)
            ->create()
            ->setCountry($countryCode)
            ->setCity($city)
            ->setTerminalId($terminalId)
            ->setDeliveryGatewayCode($this->deliveryGatewayCode)
        ;
    }
}

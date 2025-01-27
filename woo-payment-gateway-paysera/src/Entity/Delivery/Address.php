<?php

declare(strict_types=1);

namespace Paysera\Entity\Delivery;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderAddressInterface;
use Paysera\Entity\PayseraDeliverySettings;
use WC_Order;

class Address extends AbstractEntity implements MerchantOrderAddressInterface
{
    use WcOrderPartyPropertiesAccess;

    private const FIELD_COUNTRY = 'country';
    private const FIELD_CITY = 'city';
    private const FIELD_STATE = 'state';
    private const FIELD_STREET = 'address_1';
    private const FIELD_POSTAL_CODE = 'postcode';

    private WC_Order $order;

    private string $type;

    public function __construct(WC_Order $wcOrder, string $type)
    {
        $this->order = $wcOrder;
        $this->type = $type;
    }

    public function setCountry(?string $country): MerchantOrderAddressInterface
    {
        $this->setToWcOrder(self::FIELD_COUNTRY, $country ?? '');

        return $this;
    }

    public function getCountry(): string
    {
        return (string)$this->getFromWcOrder(self::FIELD_COUNTRY);
    }

    public function setState(?string $state): MerchantOrderAddressInterface
    {
        $this->setToWcOrder(self::FIELD_STATE, $state ?? '');

        return $this;
    }

    public function getState(): string
    {
        return (string)$this->getFromWcOrder(self::FIELD_STATE);
    }

    public function setCity(?string $city): MerchantOrderAddressInterface
    {
        $this->setToWcOrder(self::FIELD_CITY, $city ?? '');

        return $this;
    }

    public function getCity(): string
    {
        return (string)$this->getFromWcOrder(self::FIELD_CITY);
    }

    public function setStreet(?string $street): MerchantOrderAddressInterface
    {
        $this->setToWcOrder(self::FIELD_STREET, $street ?? '');

        return $this;
    }

    public function getStreet(): string
    {
        return (string)$this->getFromWcOrder(self::FIELD_STREET);
    }

    public function setPostalCode(?string $postalCode): MerchantOrderAddressInterface
    {
        $this->setToWcOrder(self::FIELD_POSTAL_CODE, $postalCode ?? '');

        return $this;
    }

    public function getPostalCode(): string
    {
        return (string)$this->getFromWcOrder(self::FIELD_POSTAL_CODE);
    }

    public function setHouseNumber(?string $houseNumber): MerchantOrderAddressInterface
    {
        $this->order->update_meta_data(PayseraDeliverySettings::ORDER_META_KEY_HOUSE_NO, $houseNumber ?? '');

        return $this;
    }

    public function getHouseNumber(): ?string
    {
        return $this->order->get_meta(PayseraDeliverySettings::ORDER_META_KEY_HOUSE_NO);
    }
}

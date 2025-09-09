<?php

namespace Paysera\Format;

use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Provider\PayseraDeliverySettingsProvider;

if (! defined('ABSPATH')) {
    exit;
}

class PayseraCustomAddressFormat
{
    private PayseraDeliverySettingsProvider $payseraDeliverySettingsProvider;
    private bool $isHouseNumberFieldEnabled;

    public function __construct(
        PayseraDeliverySettingsProvider $payseraDeliverySettingsProvider
    ) {
        $this->payseraDeliverySettingsProvider = $payseraDeliverySettingsProvider;
        $this->isHouseNumberFieldEnabled       = $this->getHouseNumberFieldStatus();
    }

    public function register(): void
    {
        add_filter(
            'woocommerce_order_get_formatted_billing_address',
            [$this, 'custom_get_billing_address_format'],
            10,
            3
        );

        add_filter(
            'woocommerce_order_get_formatted_shipping_address',
            [$this, 'custom_get_shipping_address_format'],
            10,
            3
        );
    }

    public function custom_get_shipping_address_format(string $address, $raw_address, $order): string
    {
        return $this->appendHouseNumber($address, $order, PayseraDeliverySettings::SHIPPING_HOUSE_NO);
    }

    public function custom_get_billing_address_format(string $address, $raw_address, $order): string
    {
        return $this->appendHouseNumber($address, $order, PayseraDeliverySettings::BILLING_HOUSE_NO);
    }

    private function getHouseNumberFieldStatus(): bool
    {
        return $this->payseraDeliverySettingsProvider
            ->getPayseraDeliverySettings()
            ->isHouseNumberFieldEnabled() ?? false;
    }

    private function appendHouseNumber(string $address, $order, string $metaKey): string
    {
        if (is_wc_endpoint_url('order-received')) {
            if ($this->isHouseNumberFieldEnabled) {
                $houseNo = $order->get_meta('_' . $metaKey, true);
                $address .= "<br>\n" . $houseNo;
            }
        }

        return $address;
    }
}

<?php

declare(strict_types=1);

namespace Paysera\Service;

use WC_Order;

class CompatibilityManager
{
    private WC_Order $order;

    public function Order(WC_Order $order): CompatibilityManager
    {
        $this->order = $order;

        return $this;
    }

    public function getShippingPhone(): ?string
    {
        $shippingPhone = $this->order->get_billing_phone();

        if (version_compare(WC()->version, '5.6.0', '<')) {
            return $shippingPhone;
        }

        if (!method_exists($this->order, 'get_shipping_phone')) {
            return $shippingPhone;
        }

        if ($this->order->get_shipping_phone() === null || $this->order->get_shipping_phone() === '') {
            return $shippingPhone;
        }

        return $this->order->get_shipping_phone();
    }
}

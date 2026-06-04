<?php

declare(strict_types=1);

namespace Paysera\Helper;

class WCSessionHelper implements SessionHelperInterface
{
    public function setData($key, $value)
    {
        if ($this->isSessionAvailable()) {
            WC()->session->set($key, $value);
        }
    }

    public function getData($key, $default = '')
    {
        if ($this->isSessionAvailable()) {
            return WC()->session->get($key, $default);
        }

        return $default;
    }

    public function isSessionAvailable(): bool
    {
        return isset(WC()->session);
    }

    public function getChosenShippingMethods(): array
    {
        if (!$this->isSessionAvailable()) {
            return [];
        }

        $chosen = WC()->session->get('chosen_shipping_methods', []);

        return is_array($chosen) ? $chosen : [];
    }
}

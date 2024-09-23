<?php

declare(strict_types=1);

namespace Paysera\Helper;

use WC_Order;

class WCOrderFieldUpdateHelper implements WCOrderUpdateHelperInterface
{
    public function canUpdate(WC_Order $order, string $nameOfTarget, $actualValue): bool
    {
        $getter = 'get_' . strtolower($nameOfTarget);

        return method_exists($order, $getter) && $order->$getter() !== $actualValue;
    }

    public function handleUpdate(WC_Order $order, string $nameOfTarget, $actualValue): void
    {
        $setter = 'set_' . strtolower($nameOfTarget);

        if (method_exists($order, $setter)) {
            $order->$setter($actualValue);
        }
    }
}

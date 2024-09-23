<?php

declare(strict_types=1);

namespace Paysera\Helper;

use WC_Order;

class WCOrderMetaUpdateHelper implements WCOrderUpdateHelperInterface
{
    public function canUpdate(WC_Order $order, string $nameOfTarget, $actualValue): bool
    {
        return
            !$order->meta_exists($nameOfTarget)
            || $order->get_meta($nameOfTarget) !== $actualValue
        ;
    }

    public function handleUpdate(WC_Order $order, string $nameOfTarget, $actualValue): void
    {
        $order->update_meta_data($nameOfTarget, $actualValue);
    }
}

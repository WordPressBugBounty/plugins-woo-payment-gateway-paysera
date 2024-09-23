<?php

declare(strict_types=1);

namespace Paysera\Helper;

use WC_Order;

interface WCOrderUpdateHelperInterface
{
    public function canUpdate(WC_Order $order, string $nameOfTarget, $actualValue): bool;

    public function handleUpdate(WC_Order $order, string $nameOfTarget, $actualValue): void;
}

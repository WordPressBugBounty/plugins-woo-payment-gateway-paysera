<?php

declare(strict_types=1);

namespace Paysera\Repository;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Repository\MerchantOrderRepositoryInterface;
use Paysera\Entity\Delivery\Order;
use WC_Order;

class OrderRepository implements MerchantOrderRepositoryInterface
{
    /**
     * @param Order $order
     * @return void
     */
    public function save(MerchantOrderInterface $order): void
    {
        $order->getActualShippingMethod()->save();
        $order->getWcOrder()->save_meta_data();
        $order->getWcOrder()->save();
    }
}

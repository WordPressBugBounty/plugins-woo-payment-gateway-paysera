<?php

declare(strict_types=1);

namespace Paysera\Repository;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderInterface;
use Paysera\Entity\Delivery\Order;
use Paysera\Factory\OrderFactory;
use WC_Order;

class MerchantOrderRepository
{
    private OrderFactory $orderFactory;

    public function __construct(OrderFactory $orderFactory)
    {
        $this->orderFactory = $orderFactory;
    }

    /**
     * @param int $orderId
     * @return MerchantOrderInterface<Order>|null
     */
    public function findOrderById(int $orderId): ?Order
    {
        /** @var WC_Order $order */
        $order = wc_get_order($orderId);

        return $order
            ? $this->orderFactory->createFromWcOrder($order)
            : null;
    }
}

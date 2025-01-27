<?php

declare(strict_types=1);

namespace Paysera\Factory;

use Paysera\Entity\Delivery\Order;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\InvalidTypeException;
use Paysera\Scoped\Psr\Container\ContainerInterface;
use WC_Order;

class OrderFactory
{
    private ContainerInterface $container;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    /**
     * @param WC_Order $wcOrder
     * @return MerchantOrderInterface<Order>
     * @throws InvalidTypeException
     */
    public function createFromWcOrder(WC_Order $wcOrder): MerchantOrderInterface
    {
        $items = $this->container
            ->get(OrderItemsCollectionFactory::class)
            ->createFormWcOrder($wcOrder)
        ;

        return new Order(
            $wcOrder,
            $items,
            $this->container,
        );
    }
}

<?php

declare(strict_types=1);

namespace Paysera\Factory;

use Paysera\Entity\Delivery\Order;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\InvalidTypeException;
use WC_Order;

class OrderFactory
{
    private OrderItemsCollectionFactory $itemsCollectionFactory;
    private PartyFactory $partyFactory;

    public function __construct(
        OrderItemsCollectionFactory $itemsCollectionFactory,
        PartyFactory $partyFactory
    ) {
        $this->itemsCollectionFactory = $itemsCollectionFactory;
        $this->partyFactory = $partyFactory;
    }

    /**
     * @param WC_Order $wcOrder
     * @return MerchantOrderInterface<Order>
     * @throws InvalidTypeException
     */
    public function createFromWcOrder(WC_Order $wcOrder): MerchantOrderInterface
    {
        $items = $this->itemsCollectionFactory
            ->createFormWcOrder($wcOrder)
        ;

        return new Order(
            $wcOrder,
            $items,
            $this->partyFactory,
        );
    }
}

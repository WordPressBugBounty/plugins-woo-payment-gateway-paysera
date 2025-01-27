<?php

declare(strict_types=1);

namespace Paysera\Factory;

use Paysera\Scoped\Paysera\DeliverySdk\Util\DeliveryGatewayUtils;
use Paysera\Entity\Delivery\Order;
use Paysera\Entity\Delivery\Party;
use Paysera\Scoped\Psr\Container\ContainerInterface;

class PartyFactory
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function createShipping(Order $order): Party
    {
        return new Party(
            $order->getWcOrder(),
            Party::TYPE_SHIPPING,
            $this->getGatewayCode($order),
            $this->container
        );
    }

    public function createBilling(Order $order): Party
    {
        return new Party(
            $order->getWcOrder(),
            Party::TYPE_BILLING,
            $this->getGatewayCode($order),
            $this->container
        );
    }

    private function getGatewayCode(Order $order)
    {
        return $this->container
            ->get(DeliveryGatewayUtils::class)
            ->resolveDeliveryGatewayCode(
                $order->getActualShippingMethod()
                    ? $order->getActualShippingMethod()->get_method_id()
                    : ''
            )
        ;
    }
}

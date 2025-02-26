<?php

declare(strict_types=1);

namespace Paysera\Factory;

use Paysera\Entity\Delivery\Order;
use Paysera\Entity\Delivery\Party;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\DeliveryTerminalLocationFactoryInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Util\DeliveryGatewayUtils;
use Paysera\Service\CompatibilityManager;

class PartyFactory
{
    private CompatibilityManager $compatibilityManager;
    private DeliveryTerminalLocationFactoryInterface $deliveryTerminalLocationFactory;
    private DeliveryGatewayUtils $deliveryGatewayUtils;

    public function __construct(
        CompatibilityManager $compatibilityManager,
        DeliveryTerminalLocationFactoryInterface $deliveryTerminalLocationFactory,
        DeliveryGatewayUtils $deliveryGatewayUtils
    ) {
        $this->compatibilityManager = $compatibilityManager;
        $this->deliveryTerminalLocationFactory = $deliveryTerminalLocationFactory;
        $this->deliveryGatewayUtils = $deliveryGatewayUtils;
    }

    public function createShipping(Order $order): Party
    {
        return new Party(
            $order->getWcOrder(),
            Party::TYPE_SHIPPING,
            $this->getGatewayCode($order),
            $this->deliveryTerminalLocationFactory,
            $this->compatibilityManager,
        );
    }

    public function createBilling(Order $order): Party
    {
        return new Party(
            $order->getWcOrder(),
            Party::TYPE_BILLING,
            $this->getGatewayCode($order),
            $this->deliveryTerminalLocationFactory,
            $this->compatibilityManager,
        );
    }

    private function getGatewayCode(Order $order)
    {
        return $this->deliveryGatewayUtils
            ->resolveDeliveryGatewayCode(
                $order->getActualShippingMethod()
                    ? $order->getActualShippingMethod()->get_method_id()
                    : ''
            )
        ;
    }
}

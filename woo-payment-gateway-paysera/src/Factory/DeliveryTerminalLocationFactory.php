<?php

declare(strict_types=1);

namespace Paysera\Factory;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\DeliveryTerminalLocationFactoryInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\DeliveryTerminalLocationInterface;
use Paysera\Entity\Delivery\DeliveryTerminalLocation;

class DeliveryTerminalLocationFactory implements DeliveryTerminalLocationFactoryInterface
{
    public function create(): DeliveryTerminalLocationInterface
    {
        return new DeliveryTerminalLocation();
    }
}
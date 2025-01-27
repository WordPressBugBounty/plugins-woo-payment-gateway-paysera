<?php

declare(strict_types=1);

namespace Paysera\Entity\Delivery;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\NotificationCallbackInterface;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\Rest\PayseraDeliveryController;

class Callback implements NotificationCallbackInterface
{
    private int $orderId;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    public function getUrl(): string
    {
        return rest_url(
            sprintf(
                '%s/%s/check-order-updates/%d',
                PayseraPaths::PAYSERA_REST_BASE,
                PayseraDeliveryController::CONTROLLER_BASE,
                $this->orderId
            )
        );
    }

    public function getEvents(): array
    {
        return [PayseraDeliverySettings::DELIVERY_ORDER_EVENT_UPDATED];
    }
}

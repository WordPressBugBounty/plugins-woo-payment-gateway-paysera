<?php

declare(strict_types=1);

namespace Paysera\Helper;

use Paysera\DeliveryApi\MerchantClient\Entity\OrderNotificationCreate;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\Rest\PayseraDeliveryController;

class CallbackHelper
{
    public function configureOrderNotificationCallback(int $orderId): OrderNotificationCreate
    {
        return (new OrderNotificationCreate())
            ->setUrl(
                rest_url(
                    sprintf(
                        '%s/%s/check-order-updates/%d',
                        PayseraPaths::PAYSERA_REST_BASE,
                        PayseraDeliveryController::CONTROLLER_BASE,
                        $orderId
                    )
                )
            )
            ->setEvents([PayseraDeliverySettings::DELIVERY_ORDER_EVENT_UPDATED])
        ;
    }
}

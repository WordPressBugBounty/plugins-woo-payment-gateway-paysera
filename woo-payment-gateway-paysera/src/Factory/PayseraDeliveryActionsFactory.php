<?php

declare(strict_types=1);

namespace Paysera\Factory;

use Paysera\Action\PayseraDeliveryActions;
use Paysera\Helper\LogHelper;
use Paysera\Provider\MerchantClientProvider;
use Paysera\Provider\PayseraDeliverySettingsProvider;

class PayseraDeliveryActionsFactory
{
    public function create(): PayseraDeliveryActions
    {
        $logger = (new LoggerFactory())->createLogger(LogHelper::LOGGER_TYPE_DELIVERY);
        $merchantClientProvider = new MerchantClientProvider($logger);
        $deliverySettingsProvider = new PayseraDeliverySettingsProvider();

        return new PayseraDeliveryActions($merchantClientProvider, $logger, $deliverySettingsProvider);
    }
}

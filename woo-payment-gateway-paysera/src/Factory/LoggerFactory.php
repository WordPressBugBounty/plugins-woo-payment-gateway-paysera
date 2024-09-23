<?php

declare(strict_types=1);

namespace Paysera\Factory;

use InvalidArgumentException;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaymentSettings;
use Paysera\Helper\LogHelper;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Provider\PayseraPaymentSettingsProvider;
use Paysera\Service\DeliveryLogger;
use Paysera\Service\LoggerInterface;
use Paysera\Service\PaymentLogger;

class LoggerFactory
{
    private static PayseraPaymentSettings $payseraPaymentSettings;
    private static PayseraDeliverySettings $payseraDeliverySettings;

    public function createLogger(string $loggerType): LoggerInterface
    {
        $logHelper = new LogHelper();

        switch ($loggerType) {
            case LogHelper::LOGGER_TYPE_DELIVERY:
                if (!isset(self::$payseraDeliverySettings)) {
                    self::$payseraDeliverySettings = (new PayseraDeliverySettingsProvider())->getPayseraDeliverySettings();
                }

                return new DeliveryLogger($logHelper, self::$payseraDeliverySettings->getLogLevel());
            case LogHelper::LOGGER_TYPE_PAYMENT:
                if (!isset(self::$payseraPaymentSettings)) {
                    self::$payseraPaymentSettings = (new PayseraPaymentSettingsProvider())->getPayseraPaymentSettings();
                }

                return new PaymentLogger($logHelper, self::$payseraPaymentSettings->getLogLevel());
            default:
                throw new InvalidArgumentException('Invalid logger type');
        }
    }
}

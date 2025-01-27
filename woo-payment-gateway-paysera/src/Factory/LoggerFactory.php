<?php

declare(strict_types=1);

namespace Paysera\Factory;

use Paysera\Scoped\Paysera\DeliverySdk\Service\DeliveryLoggerInterface;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaymentSettings;
use Paysera\Helper\LogHelper;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Provider\PayseraPaymentSettingsProvider;
use Paysera\Service\DeliveryLogger;
use Paysera\Service\PaymentLogger;
use Paysera\Service\PaymentLoggerInterface;

class LoggerFactory
{
    private static PayseraPaymentSettings $paymentSettings;
    private static PayseraDeliverySettings $deliverySettings;
    private LogHelper $logHelper;

    public function __construct(LogHelper $logHelper)
    {
        $this->logHelper = $logHelper;
    }

    public function createDeliveryLogger(): DeliveryLoggerInterface
    {
        if (!isset(self::$deliverySettings)) {
            self::$deliverySettings = (new PayseraDeliverySettingsProvider())
                ->getPayseraDeliverySettings()
            ;
        }

        return new DeliveryLogger($this->logHelper, self::$deliverySettings->getLogLevel());
    }
    public function createPaymentLogger(): PaymentLoggerInterface
    {
        if (!isset(self::$paymentSettings)) {
            self::$paymentSettings = (new PayseraPaymentSettingsProvider())->getPayseraPaymentSettings();
        }

        return new PaymentLogger($this->logHelper, self::$paymentSettings->getLogLevel());
    }
}

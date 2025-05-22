<?php

declare(strict_types=1);

namespace Paysera\Factory;

use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaymentSettings;
use Paysera\Helper\LogHelper;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Provider\PayseraPaymentSettingsProvider;
use Paysera\Scoped\Paysera\DeliverySdk\Service\DeliveryLoggerInterface;
use Paysera\Service\AdminNotice;
use Paysera\Service\DeliveryLogger;
use Paysera\Service\PaymentLogger;
use Paysera\Service\PaymentLoggerInterface;
use Paysera\Service\SelfDiagnosis\Library\Util\DiagnosticReportGenerator;

class LoggerFactory
{
    private static PayseraPaymentSettings $paymentSettings;
    private static PayseraDeliverySettings $deliverySettings;
    private LogHelper $logHelper;
    private DiagnosticReportGenerator $diagnosticReportGenerator;
    private AdminNotice $adminNotice;

    public function __construct(
        LogHelper $logHelper,
        DiagnosticReportGenerator $diagnosticJSONReportGenerator,
        AdminNotice $adminNotice
    ) {
        $this->logHelper = $logHelper;
        $this->diagnosticReportGenerator = $diagnosticJSONReportGenerator;
        $this->adminNotice = $adminNotice;

    }

    public function createDeliveryLogger(): DeliveryLoggerInterface
    {
        if (!isset(self::$deliverySettings)) {
            self::$deliverySettings = (new PayseraDeliverySettingsProvider())
                ->getPayseraDeliverySettings()
            ;
        }

        return new DeliveryLogger(
            $this->logHelper,
            $this->diagnosticReportGenerator,
            $this->adminNotice,
            self::$deliverySettings->getLogLevel()
        );
    }
    public function createPaymentLogger(): PaymentLoggerInterface
    {
        if (!isset(self::$paymentSettings)) {
            self::$paymentSettings = (new PayseraPaymentSettingsProvider())->getPayseraPaymentSettings();
        }

        return new PaymentLogger(
            $this->logHelper,
            $this->diagnosticReportGenerator,
            $this->adminNotice,
            self::$paymentSettings->getLogLevel()
        );
    }
}

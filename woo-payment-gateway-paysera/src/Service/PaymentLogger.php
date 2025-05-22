<?php

declare(strict_types=1);

namespace Paysera\Service;

use Paysera\Helper\LogHelper;
use Paysera\Service\SelfDiagnosis\Library\Util\DiagnosticReportGenerator;

class PaymentLogger implements PaymentLoggerInterface
{
    private const LOG_MESSAGE_TEMPLATE
        = 'Paysera Payment: An error has occurred. Please review the <a href="%s">logs</a> for additional details.';

    private LogHelper $logHelper;
    private DiagnosticReportGenerator $diagnosticReportGenerator;
    private AdminNotice $adminNotice;
    private string $logLevel;

    public function __construct(
        LogHelper $logHelper,
        DiagnosticReportGenerator $diagnosticReportGenerator,
        AdminNotice $adminNotice,
        string $logLevel = LogHelper::LOG_LEVEL_ERROR
    ) {
        $this->logHelper = $logHelper;
        $this->diagnosticReportGenerator = $diagnosticReportGenerator;
        $this->adminNotice = $adminNotice;
        $this->logLevel = $logLevel;
    }

    public function info(string $message): void
    {
        if ($this->logLevel !== LogHelper::LOG_LEVEL_INFO) {
            return;
        }

        file_put_contents(
            $this->logHelper->getDateWiseLogPath(LogHelper::LOGGER_TYPE_PAYMENT),
            $this->logHelper->getFormattedText('INFO', $message),
            FILE_APPEND
        );
    }

    public function error(string $message, \Throwable $exception = null): void
    {
        if ($this->logLevel === LogHelper::LOG_LEVEL_NONE) {
            return;
        }

        $this->adminNotice->addErrorNotice(
            sprintf(
                __(self::LOG_MESSAGE_TEMPLATE, 'paysera'),
                admin_url('admin.php?page=wc-status&tab=logs')
            )
        );

        if ($exception !== null) {
            $exceptionMessage = method_exists($exception, 'getResponse')
                ? $exception->getResponse()->getBody()->getContents()
                : $exception->getMessage();
            $message .= sprintf(' (%s)', $exceptionMessage);
            if ($exception->getPrevious() instanceof \Throwable) {
                $message .= sprintf(' (%s)', $exception->getPrevious()->getMessage());
            } else {
                $message .= sprintf(' - %s:%s', plugin_basename($exception->getFile()), $exception->getLine());
            }
        }

        $message .= ' CONTEXT: ' . htmlspecialchars(sprintf('{"self_diagnosis":%s}', $this->diagnosticReportGenerator->getReport()));

        file_put_contents(
            $this->logHelper->getDateWiseLogPath(LogHelper::LOGGER_TYPE_PAYMENT),
            $this->logHelper->getFormattedText('ERROR', $message),
            FILE_APPEND
        );
    }
}

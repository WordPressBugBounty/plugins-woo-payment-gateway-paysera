<?php

declare(strict_types=1);

namespace Paysera\Service;

use Paysera\Helper\LogHelper;

class PaymentLogger implements LoggerInterface
{
    private LogHelper $logHelper;
    private string $logLevel;

    public function __construct(LogHelper $logHelper, string $logLevel = LogHelper::LOG_LEVEL_ERROR)
    {
        $this->logHelper = $logHelper;
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

        file_put_contents(
            $this->logHelper->getDateWiseLogPath(LogHelper::LOGGER_TYPE_PAYMENT),
            $this->logHelper->getFormattedText('ERROR', $message),
            FILE_APPEND
        );
    }
}

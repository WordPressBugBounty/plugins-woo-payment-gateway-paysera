<?php

declare(strict_types=1);

namespace Paysera\Helper;

class LogHelper
{
    public const LOGGER_TYPE_DELIVERY = 'Delivery';
    public const LOGGER_TYPE_PAYMENT = 'Payment';

    public const LOG_LEVEL_NONE = 'none';
    public const LOG_LEVEL_ERROR = 'error';
    public const LOG_LEVEL_INFO = 'info';

    private const LOG_FILE_PREFIX = 'Paysera-';

    public function getDateWiseLogPath(string $loggerName): string
    {
        return path_join(
            WC_LOG_DIR,
            sprintf('%s%s-%s.log', self::LOG_FILE_PREFIX, $loggerName, date('Y-m-d'))
        );
    }

    public function getFormattedText(string $logLevel, string $message): string
    {
        return sprintf('[%s] [%s] %s%s', date('Y-m-d\TH:i:sP'), $logLevel, $message, PHP_EOL);
    }

    public function getLogLevelSelectOptions(): array
    {
        return [
            self::LOG_LEVEL_NONE => 'None',
            self::LOG_LEVEL_ERROR => 'Error',
            self::LOG_LEVEL_INFO => 'Info',
        ];
    }

    public function isZipArchivable(string $loggerType): bool
    {
        return class_exists('ZipArchive') && $this->isLogFilesAvailable($loggerType);
    }

    public function isLogFilesAvailable(string $loggerType): bool
    {
        return count($this->getLogFiles($loggerType)) > 0;
    }

    public function generateZipArchive(string $loggerType): ?string
    {
        if (!$this->isZipArchivable($loggerType)) {
            return null;
        }

        $zip = new \ZipArchive();
        $fileName = sprintf('%s%s-Logs-%s.zip', self::LOG_FILE_PREFIX, $loggerType, date('Y-m-d-H-i-s'));
        $filePath = path_join(WC_LOG_DIR, $fileName);

        if ($zip->open($filePath, \ZipArchive::CREATE) !== true) {
            return null;
        }

        foreach ($this->getLogFiles($loggerType) as $logFile) {
            $zip->addFile($logFile, basename($logFile));
        }

        $zip->close();

        return $filePath;
    }

    private function getLogFiles(string $loggerType): array
    {
        $filePaths = glob(path_join(WC_LOG_DIR, sprintf('%s%s-*.log', self::LOG_FILE_PREFIX, $loggerType)));

        if ($filePaths === false) {
            return [];
        }

        return $filePaths;
    }
}

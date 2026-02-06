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
        return sprintf('%s %s %s%s', date('Y-m-d\TH:i:sP'), $logLevel, $message, PHP_EOL);
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

    public function isValidLoggerType(string $loggerType): bool
    {
        return in_array($loggerType, [
            self::LOGGER_TYPE_DELIVERY,
            self::LOGGER_TYPE_PAYMENT,
        ], true);
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

    /**
     * @return string[] List of absolute file paths to log files
     */
    private function getLogFiles(string $loggerType): array
    {
        if (!$this->isValidLoggerType($loggerType)) {
            return [];
        }

        $sanitizedLoggerType = preg_replace('/[^a-zA-Z0-9_-]/', '', $loggerType);

        $pattern = path_join(WC_LOG_DIR, sprintf('%s%s-*.log', self::LOG_FILE_PREFIX, $sanitizedLoggerType));
        $filePaths = glob($pattern);

        if ($filePaths === false) {
            return [];
        }

        $allowedDir = realpath(WC_LOG_DIR);
        if ($allowedDir === false) {
            return [];
        }

        $validFiles = [];

        foreach ($filePaths as $file) {
            $realPath = realpath($file);
            if ($realPath !== false && strpos($realPath, $allowedDir) === 0) {
                $validFiles[] = $file;
            }
        }

        return $validFiles;
    }
}

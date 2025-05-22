<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;

class PHPVersionChecker extends AbstractChecker implements CompatibilityCheckerInterface
{
    protected const CHECKER_NAME = 'PHP version';
    private const SUCCESS_MESSAGE_FORMAT = 'PHP version %s is compatible';
    private const FAILURE_MESSAGE_FORMAT = 'PHP version %s is not compatible. Please use PHP %s or higher';

    public function check(): CheckerResult
    {
        $phpVersion = PHP_VERSION;
        $requiredPhpVersion = $this->config->get('php_version');
        $this->result->isSuccess = (bool)version_compare($phpVersion, $requiredPhpVersion, '>=');

        if ($this->result->isSuccess) {
            $this->result->details = sprintf(self::SUCCESS_MESSAGE_FORMAT, $phpVersion);
        } else {
            $this->result->details = sprintf(
                self::FAILURE_MESSAGE_FORMAT,
                $phpVersion,
                $requiredPhpVersion
            );
        }

        return $this->result;
    }
}

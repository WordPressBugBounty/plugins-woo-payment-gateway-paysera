<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;

class DefaultCharsetPHPIniVarChecker extends AbstractPHPIniVarChecker implements CompatibilityCheckerInterface
{
    private const VARIABLE_NAME = 'default_charset';
    private const RECOMMENDED_VALUE = 'UTF-8';
    private const FAILED_MESSAGE_FORMAT = 'php.ini variable \'default_charset\' is set to \'%s\'. The recommended value is \'UTF-8\'. Please check it in your php.ini configuration.';


    public function check(): CheckerResult
    {
        return $this->checkPhpIniVariable(self::VARIABLE_NAME, function ($value) {
            return $value === self::RECOMMENDED_VALUE;
        });
    }

    protected function getFailedMessage(string $currentValue): string
    {
        return sprintf(
            self::FAILED_MESSAGE_FORMAT,
            $currentValue
        );
    }
}

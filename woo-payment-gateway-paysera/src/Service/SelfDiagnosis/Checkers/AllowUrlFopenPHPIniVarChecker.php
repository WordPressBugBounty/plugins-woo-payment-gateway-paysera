<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;

class AllowUrlFopenPHPIniVarChecker extends AbstractPHPIniVarChecker implements CompatibilityCheckerInterface
{
    private const VARIABLE_NAME = 'allow_url_fopen';
    protected const FAILED_MESSAGE_FORMAT
        = 'php.ini variable \'allow_url_fopen\' is set to \'%s\'. Recommended to enable this feature. Please check it in your php.ini configuration.';

    public function check(): CheckerResult
    {
        return $this->checkPhpIniVariable(self::VARIABLE_NAME, function ($value) {
            return (bool)$value === true;
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

<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;
use Paysera\Service\SelfDiagnosis\Library\Util\SelfDiagnosisConfig;

class MaxInputVarsPHPIniVarChecker extends AbstractPHPIniVarChecker implements CompatibilityCheckerInterface
{
    protected const FAILED_MESSAGE_FORMAT
        = 'php.ini variable \'max_input_vars\' is set to \'%s\'. The recommended value is at least \'%s\'. Please increase it in your php.ini configuration.';

    private int $maxInputVars;

    public function __construct(SelfDiagnosisConfig $config)
    {
        parent::__construct($config);

        $this->maxInputVars = (int)$this->config->get('max_input_vars');
    }

    protected function getFailedMessage(string $currentValue): string
    {
        return sprintf(
            self::FAILED_MESSAGE_FORMAT,
            $currentValue,
            $this->maxInputVars
        );
    }

    public function check(): CheckerResult
    {
        return $this->checkPhpIniVariable('max_input_vars', function ($value) {
            return $value >= $this->maxInputVars;
        });
    }
}

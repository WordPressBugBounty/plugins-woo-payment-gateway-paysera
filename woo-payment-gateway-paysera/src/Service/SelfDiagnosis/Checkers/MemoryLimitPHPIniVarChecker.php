<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;
use Paysera\Service\SelfDiagnosis\Library\Util\SelfDiagnosisConfig;

class MemoryLimitPHPIniVarChecker extends AbstractPHPIniVarChecker implements CompatibilityCheckerInterface
{
    private const FAILED_MESSAGE_FORMAT = 'php.ini variable \'memory_limit\' is set to \'%s\'. The recommended value is at least \'%s\'. Please increase it in your php.ini configuration.';

    private string $memoryLimit;

    public function __construct(SelfDiagnosisConfig $config, array $sizeUnits = [])
    {
        parent::__construct($config, $sizeUnits);

        $this->memoryLimit = $this->config->get('memory_limit');
    }

    protected function getFailedMessage(string $currentValue): string
    {
        return sprintf(
            self::FAILED_MESSAGE_FORMAT,
            $currentValue,
            $this->memoryLimit
        );
    }

    public function check(): CheckerResult
    {
        return $this->checkPhpIniVariable('memory_limit', function ($value) {
            return $this->parseSize($value) >= $this->parseSize($this->memoryLimit);
        });
    }
}

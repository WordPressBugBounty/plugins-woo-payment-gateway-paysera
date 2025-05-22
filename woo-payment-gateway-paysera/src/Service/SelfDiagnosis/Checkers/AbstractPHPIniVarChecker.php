<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;
use Paysera\Service\SelfDiagnosis\Library\Util\SelfDiagnosisConfig;
use Paysera\Service\SelfDiagnosis\Library\Util\Unit\UnitInterface;
use RuntimeException;

abstract class AbstractPHPIniVarChecker extends AbstractChecker
{
    private const CHECKER_NAME_FORMAT = 'Check PHP.ini variable: %s';
    private const SUCCESS_MESSAGE_FORMAT = 'php.ini variable \'%s\' is set to \'%s\', which meets the requirement.';

    protected array $sizeUnits;

    /**
     * @param SelfDiagnosisConfig $config
     * @param array<UnitInterface> $sizeUnits
     */
    public function __construct(SelfDiagnosisConfig $config, array $sizeUnits = [])
    {
        parent::__construct($config);

        $this->sizeUnits = $sizeUnits;
    }

    /**
     * Checks a PHP.ini variable and validates its value.
     *
     * @param string $variableName Name of the PHP.ini variable to check.
     * @param callable|null $validator Optional validator to validate the variable's value.
     * @return CheckerResult The result of the check.
     */
    protected function checkPhpIniVariable(string $variableName, ?callable $validator = null): CheckerResult
    {
        $currentValue = ini_get($variableName);
        $isSuccess = $currentValue !== false && ($validator === null || $validator($currentValue));

        $this->result->checkName = sprintf(self::CHECKER_NAME_FORMAT, $variableName);
        $this->result->isSuccess = $isSuccess;
        $this->result->details = $this->getMessage($isSuccess, $variableName, (string)$currentValue);

        return $this->result;
    }

    /**
     * Generates a detailed message based on the check result.
     *
     * @param bool $isSuccess Whether the check was successful.
     * @param string $variableName Name of the PHP.ini variable.
     * @param mixed $currentValue The current value of the PHP.ini variable.
     * @return string The result message.
     */
    private function getMessage(bool $isSuccess, string $variableName, string $currentValue): string
    {
        if ($isSuccess) {
            return $this->getSuccessMessage($variableName, $currentValue);
        }

        return $this->getFailedMessage($currentValue);
    }

    protected function getSuccessMessage(string $variableName, string $currentValue): string
    {
        return sprintf(
            self::SUCCESS_MESSAGE_FORMAT,
            $variableName,
            $currentValue
        );
    }

    protected function parseSize(string $value): int
    {
        foreach ($this->sizeUnits as $unit) {
            if ($unit->supports($value)) {
                return $unit->getSizeInBytes($value);
            }
        }

        throw new RuntimeException('Unsupported size unit');
    }

    abstract protected function getFailedMessage(string $currentValue): string;
}

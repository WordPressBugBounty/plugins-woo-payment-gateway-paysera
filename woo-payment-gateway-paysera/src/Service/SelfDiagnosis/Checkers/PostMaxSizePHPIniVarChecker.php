<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;
use Paysera\Service\SelfDiagnosis\Library\Util\SelfDiagnosisConfig;

class PostMaxSizePHPIniVarChecker extends AbstractPHPIniVarChecker implements CompatibilityCheckerInterface
{
    private const FAILED_MESSAGE_FORMAT
        = 'php.ini variable \'post_max_size\' is set to \'%s\'. The recommended value is at least\'%s\'. Please increase it in your php.ini configuration.';

    private string $postMaxSize;

    public function __construct(SelfDiagnosisConfig $config, array $sizeUnits = [])
    {
        parent::__construct($config, $sizeUnits);

        $this->postMaxSize = $this->config->get('post_max_size');
    }
    protected function getFailedMessage(string $currentValue): string
    {
        return sprintf(
            self::FAILED_MESSAGE_FORMAT,
            $currentValue,
            $this->postMaxSize
        );
    }

    public function check(): CheckerResult
    {
        return $this->checkPhpIniVariable('post_max_size', function ($value) {
            return $this->parseSize($value) >=  $this->parseSize($this->postMaxSize);
        });
    }
}

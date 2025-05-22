<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;
use Paysera\Service\SelfDiagnosis\Library\Util\SelfDiagnosisConfig;

abstract class AbstractChecker
{
    protected const CHECKER_NAME = 'Abstract Checker';

    protected SelfDiagnosisConfig $config;
    protected CheckerResult $result;

    public function __construct(SelfDiagnosisConfig $config)
    {
        $this->config = $config;
        $this->result = new CheckerResult();
        $this->result->checkName = static::CHECKER_NAME;
    }
}

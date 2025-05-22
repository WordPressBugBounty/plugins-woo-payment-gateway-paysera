<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library;

use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;

interface CompatibilityCheckerInterface
{
    public function check(): CheckerResult;
}

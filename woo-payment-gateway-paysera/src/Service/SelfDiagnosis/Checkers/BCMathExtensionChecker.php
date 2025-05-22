<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Checkers;

use Paysera\Service\SelfDiagnosis\Library\CompatibilityCheckerInterface;
use Paysera\Service\SelfDiagnosis\Library\Result\CheckerResult;

class BCMathExtensionChecker extends AbstractExtensionChecker implements CompatibilityCheckerInterface
{
    public function check(): CheckerResult
    {
        return $this->checkExtension('bcmath');
    }
}

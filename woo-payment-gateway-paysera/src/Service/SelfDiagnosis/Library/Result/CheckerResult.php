<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library\Result;

class CheckerResult {
    public string $checkName;
    public bool $isSuccess;
    public string $details;
}

<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library\Util;

interface SelfDiagnosisConfigLoaderInterface
{
    public function getConfig(): array;
}

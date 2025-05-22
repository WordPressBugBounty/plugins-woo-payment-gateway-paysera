<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library\Util\Unit;

interface UnitInterface
{
    public function supports(string $value): bool;
    public function getSizeInBytes(string $value): int;
}

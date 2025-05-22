<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library\Util\Unit;

class MegabyteUnit extends AbstractUnit implements UnitInterface
{
    protected const UNIT = 'M';
    protected const UNIT_MULTIPLIER = 1024 * 1024;

    public function __construct()
    {
        $this->unit = self::UNIT;
        $this->unitMultiplier = self::UNIT_MULTIPLIER;
    }
}

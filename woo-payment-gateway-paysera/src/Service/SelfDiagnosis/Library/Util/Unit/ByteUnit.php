<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library\Util\Unit;

class ByteUnit extends AbstractUnit implements UnitInterface
{
    protected const UNIT = 'B';
    protected const UNIT_MULTIPLIER = 1;

    public function __construct()
    {
        $this->unit = self::UNIT;
        $this->unitMultiplier = self::UNIT_MULTIPLIER;
    }

    public function supports($value): bool
    {
        $trimmedValue = trim($value, 'a..zA..Z');
        return $trimmedValue === $value || parent::supports($value);
    }
}

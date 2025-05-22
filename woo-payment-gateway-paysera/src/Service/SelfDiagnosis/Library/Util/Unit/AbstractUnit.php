<?php

declare(strict_types=1);

namespace Paysera\Service\SelfDiagnosis\Library\Util\Unit;

abstract class AbstractUnit
{
    protected string $unit;
    protected int $unitMultiplier;

    public function supports(string $value): bool
    {
        return strtoupper(substr($value, -1)) === $this->unit;
    }
    public function getSizeInBytes(string $value): int
    {
        return $this->getNumericValue($value) * $this->unitMultiplier;
    }

    protected function getNumericValue(string $value): int
    {
        if (!$this->supports($value)) {
           throw new \InvalidArgumentException(sprintf('Value "%s" is not supported', $value));
        }
        return (int) rtrim($value, sprintf('%s%s', strtolower($this->unit), strtoupper($this->unit)));
    }
}

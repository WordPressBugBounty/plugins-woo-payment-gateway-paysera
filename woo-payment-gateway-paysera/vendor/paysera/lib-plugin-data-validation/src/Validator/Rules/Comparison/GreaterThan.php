<?php

declare(strict_types=1);

namespace Paysera\DataValidator\Validator\Rules\Comparison;

class GreaterThan extends AbstractComparison
{
    protected string $name = 'greater-than';

    /**
     * @param mixed $value
     * @param mixed $lowerBound
     * @return bool
     */
    protected function compare($value, $lowerBound): bool
    {
        return (float) $value > (float) $lowerBound;
    }
}

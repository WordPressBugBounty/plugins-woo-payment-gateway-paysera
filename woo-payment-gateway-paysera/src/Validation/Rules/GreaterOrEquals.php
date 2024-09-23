<?php

declare(strict_types=1);

namespace Paysera\Validation\Rules;

class GreaterOrEquals extends AbstractComparison
{
    protected string $name = 'greater-or-equals';

    /**
     * @inheritDoc
     */
    protected function compare($value, $lowerBound): bool
    {
        return (float) $value >= (float) $lowerBound;
    }
}

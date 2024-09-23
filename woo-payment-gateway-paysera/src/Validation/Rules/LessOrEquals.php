<?php

declare(strict_types=1);

namespace Paysera\Validation\Rules;

class LessOrEquals extends AbstractComparison
{
    protected string $name = 'less-or-equals';

    protected function compare($value, $lowerBound): bool
    {
        return (float) $value <= (float) $lowerBound;
    }
}

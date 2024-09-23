<?php

declare(strict_types=1);

namespace Paysera\Validation\Rules;

use Paysera\DataValidator\Validator\AbstractValidator;
use Paysera\DataValidator\Validator\Exception\IncorrectValidationRuleStructure;
use Paysera\DataValidator\Validator\Rules\Comparison\AbstractComparison as BaseAbstractComparison;

abstract class AbstractComparison extends BaseAbstractComparison
{
    /**
     * @param AbstractValidator $validator
     * @param array<string, mixed> $data
     * @param string $pattern
     * @param array<int, string> $parameters
     * @return bool
     * @throws IncorrectValidationRuleStructure
     */
    public function validate(AbstractValidator $validator, array $data, string $pattern, array $parameters): bool
    {
        $fieldToCompare = $parameters[0];
        $lowerBound = $validator->getValue($data, $fieldToCompare);

        if (!is_numeric($lowerBound)) {
            return true;
        }

        return parent::validate($validator, $data, $pattern, $parameters);
    }
}

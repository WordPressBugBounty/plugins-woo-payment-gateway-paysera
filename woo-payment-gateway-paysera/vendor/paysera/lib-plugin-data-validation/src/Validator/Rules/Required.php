<?php

declare(strict_types=1);

namespace Paysera\DataValidator\Validator\Rules;

use Paysera\DataValidator\Validator\AbstractValidator;
use Paysera\DataValidator\Validator\Exception\IncorrectValidationRuleStructure;

class Required extends AbstractRule
{
    protected string $name = 'required';

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $parameters
     * @throws IncorrectValidationRuleStructure
     */
    public function validate(AbstractValidator $validator, array $data, string $pattern, array $parameters): bool
    {
        $isValid = true;
        foreach ($validator->getValues($data, $pattern) as $attribute => $value) {
            // not allowed: null, '', [], empty instance Countable
            if ($this->isFilled($value)) {
                continue;
            }

            $validator->addError($attribute, $this->getName());
            $isValid = false;
        }

        return $isValid;
    }
}

<?php

declare(strict_types=1);

namespace Paysera\DataValidator\Validator\Rules;

use Paysera\DataValidator\Validator\AbstractValidator;
use Paysera\DataValidator\Validator\Exception\IncorrectValidationRuleStructure;

class Min extends AbstractRule
{
    protected string $name = 'min';

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $parameters
     * @throws IncorrectValidationRuleStructure
     */
    public function validate(AbstractValidator $validator, array $data, string $pattern, array $parameters): bool
    {
        $min = $parameters[0];
        $values = $validator->getValues($data, $pattern);
        if (empty($values)) {
            $validator->addError($pattern, $this->getName(), [
                ':min' => $min,
                ':value' => '',
            ]);
            return false;
        }

        $isValid = true;
        foreach ($values as $attribute => $value) {
            if ($value === '0') {
                continue;
            }

            if (is_numeric($value) && (float) $value >= (float) $min) {
                break;
            }

            $validator->addError($attribute, $this->getName(), [
                ':min' => (string) $min,
                ':value' => (string) $value,
            ]);
            $isValid = false;
        }

        return $isValid;
    }
}

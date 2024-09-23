<?php

declare(strict_types=1);

namespace Paysera\DataValidator\Validator\Rules\Comparison;

use Paysera\DataValidator\Validator\AbstractValidator;
use Paysera\DataValidator\Validator\Exception\IncorrectValidationRuleStructure;
use Paysera\DataValidator\Validator\Rules\AbstractRule;

abstract class AbstractComparison extends AbstractRule
{
    protected string $name = '';

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $parameters
     * @throws IncorrectValidationRuleStructure
     */
    public function validate(AbstractValidator $validator, array $data, string $pattern, array $parameters): bool
    {
        $fieldToCompare = $parameters[0];
        $lowerBound = $validator->getValue($data, $fieldToCompare);

        $values = $validator->getValues($data, $pattern);
        if (empty($values)) {
            $validator->addError($pattern, $this->getName(), [
                ':fieldToCompare' => $fieldToCompare,
                ':valueToCompare' => $lowerBound,
            ]);
            return false;
        }

        $isValid = true;
        foreach ($values as $attribute => $value) {
            if (!is_numeric($value)) {
                $isValid = false;
            }

            if ($isValid) {
                // here it is not a strict comparison, since it can be 0 and "0"
                if (!is_numeric($lowerBound) || empty($lowerBound) && $lowerBound != 0) {
                    $isValid = false;
                    break;
                }
            }

            if ($isValid) {
                if (!$this->compare($value, $lowerBound)) {
                    $isValid = false;
                }
            }

            if ($isValid) {
                break;
            }

            $validator->addError($attribute, $this->getName(), [
                ':fieldToCompare' => $fieldToCompare,
                ':valueToCompare' => (string) $lowerBound,
            ]);
        }

        return $isValid;
    }

    /**
     * @param mixed $value
     * @param mixed $lowerBound
     * @return bool
     */
    abstract protected function compare($value, $lowerBound): bool;
}

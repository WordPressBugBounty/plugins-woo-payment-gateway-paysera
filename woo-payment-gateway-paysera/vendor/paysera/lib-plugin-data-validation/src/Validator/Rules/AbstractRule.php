<?php

declare(strict_types=1);

namespace Paysera\DataValidator\Validator\Rules;

use Countable;
use Paysera\DataValidator\Validator\AbstractValidator;
use Paysera\DataValidator\Validator\Exception\IncorrectValidationRuleStructure;

abstract class AbstractRule
{
    protected string $name = '';

    /**
     * @throws IncorrectValidationRuleStructure
     */
    public function getName(): string
    {
        if (empty($this->name)) {
            throw new IncorrectValidationRuleStructure('You must reset the $name property value');
        }
        return $this->name;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $parameters
     */
    abstract public function validate(AbstractValidator $validator, array $data, string $pattern, array $parameters): bool;

    /**
     * @param mixed $value
     */
    protected function isFilled($value): bool
    {
        return !(
            (is_null($value)) ||
            ($value === '') ||
            ((is_array($value) || is_a($value, Countable::class)) && empty($value))
        );
    }
}

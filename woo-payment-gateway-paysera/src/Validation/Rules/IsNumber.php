<?php

declare(strict_types=1);

namespace Paysera\Validation\Rules;

use Paysera\DataValidator\Validator\AbstractValidator;
use Paysera\DataValidator\Validator\Rules\AbstractRule;
use Paysera\Entity\PayseraDeliverySettings;

class IsNumber extends AbstractRule
{
    protected string $name = 'is-number';

    private array $options = [
        PayseraDeliverySettings::OPTION_DECIMAL_SEPARATOR => '.',
    ];

    private string $numbersRegex = '/^(-?(?!0\d)\d*(?:\.\d+)?|0?\.\d+)$/';

    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);
        $this->numbersRegex = sprintf(
            $this->numbersRegex,
            $this->options[PayseraDeliverySettings::OPTION_DECIMAL_SEPARATOR]
        );
    }

    /**
     * @inheritDoc
     */
    public function validate(AbstractValidator $validator, array $data, string $pattern, array $parameters): bool
    {
        $values = $validator->getValues($data, $pattern);
        if (empty($values)) {
            $validator->addError($pattern, $this->getName());

            return false;
        }

        $isValid = true;
        foreach ($values as $attribute => $value) {
            if (
                (is_string($value) && is_numeric($value) && preg_match($this->numbersRegex, $value))
                || is_float($value)
                || is_int($value)
            ) {
                break;
            }

            $validator->addError($attribute, $this->getName());
            $isValid = false;
        }

        return $isValid;
    }
}

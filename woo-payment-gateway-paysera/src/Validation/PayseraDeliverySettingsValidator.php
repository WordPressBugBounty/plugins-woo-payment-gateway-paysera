<?php

declare(strict_types=1);

namespace Paysera\Validation;

use Paysera\DataValidator\Validator\AbstractValidator;
use Paysera\DataValidator\Validator\Exception\IncorrectValidationRuleStructure;
use Paysera\DataValidator\Validator\Rules\Min;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Exception\ValidationException;
use Paysera\Validation\Rules\GreaterOrEquals as GreaterOrEqualsFieldValue;
use Paysera\Validation\Rules\IsNumber;
use Paysera\Validation\Rules\LessOrEquals as LessOrEqualsFieldValue;

class PayseraDeliverySettingsValidator extends AbstractValidator
{
    private array $fieldsTitles;

    private array $errorsTemplates;

    private array $ruleSet;

    private array $options = [
        PayseraDeliverySettings::OPTION_DECIMAL_SEPARATOR => '.',
    ];

    /**
     * @throws IncorrectValidationRuleStructure
     * @param array $fieldsTitles
     * @param array $errorsTemplates
     * @param array $ruleSet
     * @param array $options
     */
    public function __construct(
        array $fieldsTitles,
        array $errorsTemplates,
        array $ruleSet,
        array $options = []
    ) {
        parent::__construct();

        $this->fieldsTitles = $fieldsTitles;
        $this->errorsTemplates = $errorsTemplates;
        $this->ruleSet = $ruleSet;
        $this->options = array_merge($this->options, $options);

        $this->configureMinRule();
        $this->configureGreaterOrEqualsFieldValueRule();
        $this->configureLessOrEqualsFieldValueRule();
        $this->configureIsNumberRule();
    }

    public function addError(string $attribute, string $rule, array $replacements = []): void
    {
        $replacements[':attribute'] = $attribute;
        $replacements = $this->prepareFieldNamesForError($replacements);

        parent::addError($attribute, $rule, $replacements);
    }

    /**
     * @throws ValidationException
     * @param string $fieldName
     * @param array $formData
     * @param ?string $prefix
     */
    public function validateFieldValueOrFail(string $fieldName, array $formData, ?string $prefix = null): void
    {
        $rules = $this->ruleSet[$fieldName] ?? null;

        if (!$rules) {
            return;
        }

        if (!$this->validate($formData, [$fieldName => $rules])) {
            $firstError = $this->getFieldFirstError($fieldName);

            if ($prefix) {
                $prefix = $prefix . ' ';
            }

            if ($firstError) {
                throw new ValidationException($fieldName, $prefix . $firstError);
            }
        }
    }

    private function getFieldFirstError(string $fieldName): ?string
    {
        $errors = $this->getProcessedErrors();

        if (empty($errors[$fieldName])) {
            return null;
        }

        return current($errors[$fieldName]);
    }

    /**
     * @throws IncorrectValidationRuleStructure
     */
    private function configureMinRule(): void
    {
        $rule = new Min();
        $this->addRule($rule);
        $this->setRuleMessage(
            $rule->getName(),
            $this->errorsTemplates[PayseraDeliverySettings::VALIDATION_ERROR_MIN_VALUE]
        );
    }

    /**
     * @throws IncorrectValidationRuleStructure
     */
    private function configureGreaterOrEqualsFieldValueRule(): void
    {
        $rule = new GreaterOrEqualsFieldValue();
        $this->addRule($rule);
        $this->setRuleMessage(
            $rule->getName(),
            $this->errorsTemplates[PayseraDeliverySettings::VALIDATION_ERROR_GRATER_OR_EQUALS]
        );
    }

    /**
     * @throws IncorrectValidationRuleStructure
     */
    private function configureLessOrEqualsFieldValueRule(): void
    {
        $rule = new LessOrEqualsFieldValue();
        $this->addRule($rule);
        $this->setRuleMessage(
            $rule->getName(),
            $this->errorsTemplates[PayseraDeliverySettings::VALIDATION_ERROR_LESS_OR_EQUALS]
        );
    }

    /**
     * @throws IncorrectValidationRuleStructure
     */
    private function configureIsNumberRule(): void
    {
        $rule = new IsNumber($this->options);
        $this->addRule($rule);
        $this->setRuleMessage(
            $rule->getName(),
            $this->errorsTemplates[PayseraDeliverySettings::VALIDATION_ERROR_IS_NUMBER]
        );
    }

    private function prepareFieldNamesForError(array $replacements = []): array
    {
        $targetParameters = [
            ':attribute',
            ':fieldToCompare',
        ];

        foreach ($replacements as $parameterName => &$replacement) {
            if (!in_array($parameterName, $targetParameters)) {
                continue;
            }

            $title = $this->getFieldTitle($replacement);

            if ($title) {
                $replacement = $title;
            }
        }

        return $replacements;
    }

    private function getFieldTitle(string $fieldName): ?string
    {
        return $this->fieldsTitles[$fieldName] ?? null;
    }
}

<?php

declare(strict_types=1);

namespace Paysera\DataValidator\Validator\Rules;

use Paysera\DataValidator\Validator\AbstractValidator;
use Paysera\DataValidator\Validator\Contract\RepositoryInterface;
use Paysera\DataValidator\Validator\Exception\IncorrectValidationRuleStructure;

class EntityExists extends AbstractRule
{
    protected string $name = 'entity-exists';

    protected RepositoryInterface $repository;

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $parameters
     * @throws IncorrectValidationRuleStructure
     */
    public function validate(AbstractValidator $validator, array $data, string $pattern, array $parameters): bool
    {
        $isValid = true;
        foreach ($validator->getValues($data, $pattern) as $attribute => $value) {
            if (!empty($value)) {
                if ($this->repository->find((int) $value)) {
                    break;
                }
            }

            $validator->addError($attribute, $this->getName(), [':id' => (string) $value]);
            $isValid = false;
        }

        return $isValid;
    }
}

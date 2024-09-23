<?php

declare(strict_types=1);

namespace Paysera\DataValidator\Tests\Validator\Rules;

use Paysera\DataValidator\Validator\AbstractValidator;
use Paysera\DataValidator\Validator\Exception\IncorrectValidationRuleStructure;
use Paysera\DataValidator\Validator\Rules\AbstractRule;
use PHPUnit\Framework\TestCase;

class AbstractRuleTest extends TestCase
{
    public function testRuleStructure()
    {
        $someRule = new class () extends AbstractRule {
            public function validate(AbstractValidator $validator, $data, $pattern, $parameters): bool
            {
                return true;
            }
        };

        $this->expectException(IncorrectValidationRuleStructure::class);
        $this->expectExceptionMessage('You must reset the $name property value');
        $someRule->getName();
    }
}

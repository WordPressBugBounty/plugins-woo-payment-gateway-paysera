<?php

declare(strict_types=1);

namespace Paysera\DataValidator\Tests\Validator;

use Paysera\DataValidator\Validator\AbstractValidator;
use Paysera\DataValidator\Validator\Contract\RepositoryInterface;
use Paysera\DataValidator\Validator\Rules\EntityExists;
use Paysera\DataValidator\Validator\Rules\Comparison\GreaterThan;
use Paysera\DataValidator\Validator\Rules\Comparison\LessThan;
use Paysera\DataValidator\Validator\Rules\Min;
use Paysera\DataValidator\Validator\Rules\Required;
use PHPUnit\Framework\TestCase;

class AbstractValidatorTest extends TestCase
{
    public function testNoValidationRules()
    {
        $validator = new class () extends AbstractValidator { };

        $this->assertTrue($validator->validate([], []));
    }

    public function getTestedData(): iterable
    {
        $entityId = "3";
        $repositoryMock = $this->createMock(RepositoryInterface::class);
        $repositoryMock->expects($this->once())
            ->method('find')
            ->with($entityId)
            ->willReturn([
                3 => 'some value that means that the entity exists',
            ]);

        yield 'entity exists' => [
            $entityId,
            $repositoryMock,
            [
                'field' => "3",
            ],
            [
                'field' => 'entity-exists',
            ],
            true,
            [],
        ];

        $repositoryMock = $this->createMock(RepositoryInterface::class);
        $repositoryMock->expects($this->once())
            ->method('find')
            ->with($entityId)
            ->willReturn([]);

        yield 'entity does not exist' => [
            $entityId,
            $repositoryMock,
            [
                'field' => "3",
            ],
            [
                'field' => 'entity-exists',
            ],
            false,
            [
                'field' => [
                    'entity-exists' => 'error message for entity-exists rule',
                ],
            ],
        ];

        $repositoryMock = $this->createMock(RepositoryInterface::class);
        $repositoryMock->expects($this->once())
            ->method('find')
            ->with($entityId)
            ->willReturn([
                3 => 'some value that means that the entity exists',
            ]);

        yield 'min validation rule, comparison is correct ' => [
            $entityId,
            $repositoryMock,
            [
                'min__correct_field' => 0,
                'min_incorrect_field' => 1,
            ],
            [
                'min_field' => 'min:0',
                'min_incorrect_field' => 'min:5',
            ],
            false,
            [
                'min_incorrect_field' => [
                    'min' => 'the value must be numeric and at least 5',
                ],
            ],
        ];
    }

    public function testRealValidator(): void
    {
        $entityId = 3;
        $repositoryMock = $this->createMock(RepositoryInterface::class);
        $repositoryMock->expects($this->exactly(3))
            ->method('find')
            ->withConsecutive(
                [$entityId],
                [4],
                [5]
            )
            ->willReturnOnConsecutiveCalls(
                [
                    3 => 'some value that means that the entity exists',
                ],
                [],
                []
            );

        $realValidator = new class ($repositoryMock) extends AbstractValidator {
            public function __construct(RepositoryInterface $repository)
            {
                parent::__construct();

                $this->addRule(new EntityExists($repository));
                $this->addRule(new Required());
                $this->addRule(new Min());
                $this->addRule(new LessThan());
                $this->addRule(new GreaterThan());

                $this->setRuleMessage('entity-exists', 'error message for entity-exists rule');
                $this->setRuleMessage('required', 'the field is required and cannot be empty');
                $this->setRuleMessage('min', 'the value must be numeric and at least :min');
                $this->setRuleMessage(
                    'less-than',
                    'The field must be numeric and must have a value less than in the ":fieldToCompare".'
                );
                $this->setRuleMessage(
                    'greater-than',
                    'The field must be numeric and must have a value greater than in the ":fieldToCompare".'
                );

                $this->setAttributeMessage(
                    'field_entity_exists_incorrect_with_custom_error_message',
                    'customized error message for specific field'
                );
                $this->setAttributeMessage(
                    'field_required_incorrect_with_custom_error_message',
                    'The field is required and cannot be empty'
                );
                $this->setAttributeMessage(
                    'min_incorrect_field_min_value_5_with_custom_error_message',
                    'the value must be numeric and at least :min. Provided value is :value'
                );
                $this->setAttributeMessage(
                    'less_than_incorrect_field_fieldToCompare_is_min_correct_field_with_custom_error_message',
                    'The field must be numeric and must have a value less than in the ":fieldToCompare". '
                    . 'Provided value is ":valueToCompare"'
                );
                $this->setAttributeMessage(
                    'greater_than_incorrect_field_fieldToCompare_is_min_correct_field_with_custom_error_message',
                    'The field must be numeric and must have a value greater than in the ":fieldToCompare". '
                    . 'Provided value is ":valueToCompare"'
                );
            }
        };

        $values = [
            'field_entity_exists_correct' => $entityId,
            'field_entity_exists_incorrect' => 4,
            'field_entity_exists_incorrect_with_custom_error_message' => 5,

            'field_required_correct' => 'value',
            'field_required_incorrect' => '',
            'field_required_incorrect_with_custom_error_message' => '',

            'min_correct_field' => 0,
            'min_incorrect_field_min_value_5' => 0,
            'min_incorrect_field_min_value_5_with_custom_error_message' => 0,

            'less_than_correct_field_fieldToCompare_is_min_correct_field' => -1,
            'less_than_incorrect_field_fieldToCompare_is_min_correct_field' => 5,
            'less_than_incorrect_field_fieldToCompare_is_min_correct_field_with_custom_error_message' => 5,

            'greater_than_correct_field_fieldToCompare_is_min_correct_field' => 5,
            'greater_than_incorrect_field_fieldToCompare_is_min_correct_field' => 0,
            'greater_than_incorrect_field_fieldToCompare_is_min_correct_field_with_custom_error_message' => 0,
        ];

        $rules = [
            'field_entity_exists_correct' => 'entity-exists',
            'field_entity_exists_incorrect' => 'entity-exists',
            'field_entity_exists_incorrect_with_custom_error_message' => 'entity-exists',

            'field_required_correct' => 'required',
            'field_required_incorrect' => 'required',
            'field_required_incorrect_with_custom_error_message' => 'required',

            'min_correct_field' => 'min:0',
            'min_incorrect_field_min_value_5' => 'min:5',
            'min_incorrect_field_min_value_5_with_custom_error_message' => 'min:5',

            'less_than_correct_field_fieldToCompare_is_min_correct_field' => 'less-than:min_correct_field',
            'less_than_incorrect_field_fieldToCompare_is_min_correct_field' => 'less-than:min_correct_field',
            'less_than_incorrect_field_fieldToCompare_is_min_correct_field_with_custom_error_message' =>
                'less-than:min_correct_field',

            'greater_than_correct_field_fieldToCompare_is_min_correct_field' => 'greater-than:min_correct_field',
            'greater_than_incorrect_field_fieldToCompare_is_min_correct_field' => 'greater-than:min_correct_field',
            'greater_than_incorrect_field_fieldToCompare_is_min_correct_field_with_custom_error_message' =>
                'greater-than:min_correct_field',
        ];

        $errors = [
            'field_entity_exists_incorrect' => [
                'entity-exists' => 'error message for entity-exists rule',
            ],
            'field_entity_exists_incorrect_with_custom_error_message' => [
                'entity-exists' => 'customized error message for specific field',
            ],
            'field_required_incorrect' => [
                'required' => 'the field is required and cannot be empty',
            ],
            'field_required_incorrect_with_custom_error_message' => [
                'required' => 'The field is required and cannot be empty',
            ],

            'min_incorrect_field_min_value_5' => [
                'min' => 'the value must be numeric and at least 5',
            ],
            'min_incorrect_field_min_value_5_with_custom_error_message' => [
                'min' => 'the value must be numeric and at least 5. Provided value is 0',
            ],

            'less_than_incorrect_field_fieldToCompare_is_min_correct_field' => [
                'less-than' => 'The field must be numeric and must have a value less than in the "Min correct field".',
            ],
            'less_than_incorrect_field_fieldToCompare_is_min_correct_field_with_custom_error_message' => [
                'less-than' => 'The field must be numeric and must have a value less than in the "Min correct field". '
                    . 'Provided value is "0"',
            ],

            'greater_than_incorrect_field_fieldToCompare_is_min_correct_field' => [
                'greater-than' => 'The field must be numeric and must have a value greater than in the "Min correct field".',
            ],
            'greater_than_incorrect_field_fieldToCompare_is_min_correct_field_with_custom_error_message' => [
                'greater-than' => 'The field must be numeric and must have a value greater than in the "Min correct field". '
                    . 'Provided value is "0"',
            ],
        ];

        $this->assertFalse($realValidator->validate($values, $rules));

        $this->assertEquals(
            $errors,
            $realValidator->getProcessedErrors()
        );
    }

    public function getComplexTestedData(): iterable
    {
        $values = [
            'min_correct_field' => 5,
            'field_with_two_validation_rules' => 4,
        ];

        $rules = [
            'min_correct_field' => 'min:5|less-than:field_with_two_validation_rules',
            'field_with_two_validation_rules' => 'min:5|greater-than:min_correct_field',
        ];

        $errors = [
            'field_with_two_validation_rules' => [
                'min' => 'the value must be numeric and at least 5',
            ],
        ];

        yield 'only min error is expected' => [
            $values,
            $rules,
            $errors,
            'min',
        ];

        $values = [
            'min_correct_field' => 5,
            'field_with_two_validation_rules' => 5,
        ];

        $errors = [
            'field_with_two_validation_rules' => [
                'greater-than' => 'The field must be numeric and must have a value greater than in the "Min correct field".',
            ],
        ];

        yield 'only greater-than error is expected' => [
            $values,
            $rules,
            $errors,
            'greater-than',
        ];
    }

    /**
     * @dataProvider getComplexTestedData
     */
    public function testComplexValidationRulesStopOnError(array $values, array $rules, array $errors, string $wrongRule)
    {
        $realValidator = new class () extends AbstractValidator {
            public function __construct()
            {
                parent::__construct();

                $this->addRule(new Min());
                $this->addRule(new GreaterThan());

                $this->setRuleMessage('min', 'the value must be numeric and at least :min');
                $this->setRuleMessage(
                    'greater-than',
                    'The field must be numeric and must have a value greater than in the ":fieldToCompare".'
                );
            }
        };

        $this->assertFalse($realValidator->validate($values, $rules));

        $processedErrors = $realValidator->getProcessedErrors();

        $this->assertEquals(
            $errors,
            $processedErrors
        );

        $this->assertArrayHasKey($wrongRule, $processedErrors['field_with_two_validation_rules']);
        $this->assertCount(1, $processedErrors['field_with_two_validation_rules']);
    }

    public function testGetValue()
    {
        $validator = new class () extends AbstractValidator { };

        $values = [
            'a' => [
                'b' => [
                    'c' => 'value',
                ],
            ],
        ];

        $this->assertEquals(
            'value',
            $validator->getValue($values, 'a.b.c')
        );

        $this->assertNull(
            $validator->getValue($values, 'a.b.d')
        );
    }

    public function testGetValues()
    {
        $validator = new class () extends AbstractValidator { };

        $values = [
            'a' => [
                'b' => [
                    'c' => 'value',
                ],
            ],
        ];

        $this->assertEquals(
            [
                'a.b.c' => 'value',
            ],
            iterator_to_array($validator->getValues($values, 'a.b.*'))
        );
    }
}

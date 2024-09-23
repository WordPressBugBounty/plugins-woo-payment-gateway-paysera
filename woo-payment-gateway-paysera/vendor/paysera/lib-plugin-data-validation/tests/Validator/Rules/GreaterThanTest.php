<?php

declare(strict_types=1);

namespace Paysera\DataValidator\Validator\Rules;

use Paysera\DataValidator\Validator\AbstractValidator;
use Paysera\DataValidator\Validator\Rules\Comparison\GreaterThan;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GreaterThanTest extends TestCase
{
    public function testGetName()
    {
        $greaterThanRule = new GreaterThan();

        $this->assertEquals(
            'greater-than',
            $greaterThanRule->getName()
        );
    }

    /**
     * @param array $data
     * @param string $pattern
     * @param mixed $value
     * @param string $fieldToComparePattern
     * @param mixed $fieldToCompareValue
     * @return (object&MockObject)|AbstractValidator|(AbstractValidator&object&MockObject)|(AbstractValidator&MockObject)|MockObject
     */
    protected function createAbstractValidatorMock(
        array $data,
        string $pattern,
        $value,
        string $fieldToComparePattern,
        $fieldToCompareValue
    ) {
        $validatorMock = $this->createMock(AbstractValidator::class);
        $validatorMock->expects($this->once())
            ->method('getValue')
            ->with($data, $fieldToComparePattern)
            ->willReturn($fieldToCompareValue);
        $validatorMock->expects($this->once())
            ->method('getValues')
            ->with($data, $pattern)
            ->willReturn([
                $pattern => $value,
            ]);
        return $validatorMock;
    }

    public function getTestedData(): iterable
    {
        $pattern = 'maximum_weight';
        $value = 5;
        $fieldToComparePattern = 'minimum_weight';
        $fieldToCompareValue = 3;
        $data = [
            $pattern => $value,
            $fieldToComparePattern => $fieldToCompareValue,
        ];
        $validatorMock = $this->createAbstractValidatorMock(
            $data,
            $pattern,
            $value,
            $fieldToComparePattern,
            $fieldToCompareValue
        );
        $validatorMock->expects($this->never())
            ->method('addError');

        yield 'value is correct; value to compare is correct' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                $fieldToComparePattern,
            ],
            true,
        ];

        $value = 3;
        $data = [
            $pattern => $value,
            $fieldToComparePattern => $fieldToCompareValue,
        ];
        $validatorMock = $this->createAbstractValidatorMock(
            $data,
            $pattern,
            $value,
            $fieldToComparePattern,
            $fieldToCompareValue
        );
        $validatorMock->expects($this->once())
            ->method('addError')
            ->with($pattern, 'greater-than', [
                ':fieldToCompare' => $fieldToComparePattern,
                ':valueToCompare' => $fieldToCompareValue,
            ])
            ->willReturnCallback(function ($field, $ruleName) use ($pattern) {
                $this->assertEquals($pattern, $field);
                $this->assertEquals('greater-than', $ruleName);
            });

        yield 'value is not greater than value of the field to compare' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                $fieldToComparePattern,
            ],
            false,
        ];

        $data = [
            $fieldToComparePattern => $fieldToCompareValue,
        ];
        // Special setting for the Mock;
        //that's why we need to create a new one instead of using the creation method
        $validatorMock = $this->createMock(AbstractValidator::class);
        $validatorMock->expects($this->once())
            ->method('getValue')
            ->with($data, $fieldToComparePattern)
            ->willReturn($fieldToCompareValue);
        $validatorMock->expects($this->once())
            ->method('getValues')
            ->with($data, $pattern)
            ->willReturn([]);
        $validatorMock->expects($this->once())
            ->method('addError')
            ->with($pattern, 'greater-than', [
                ':fieldToCompare' => $fieldToComparePattern,
                ':valueToCompare' => $fieldToCompareValue,
            ])
            ->willReturnCallback(function ($field, $ruleName) use ($pattern) {
                $this->assertEquals($pattern, $field);
                $this->assertEquals('greater-than', $ruleName);
            });

        yield 'value is not sent' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                $fieldToComparePattern,
            ],
            false,
        ];

        $fieldToCompareValue = null;
        $data = [
            $pattern => $value,
        ];
        $validatorMock = $this->createAbstractValidatorMock(
            $data,
            $pattern,
            $value,
            $fieldToComparePattern,
            $fieldToCompareValue
        );
        $validatorMock->expects($this->never())
            ->method('addError');

        yield 'value is numeric; value to compare is not sent; nothing to compare' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                $fieldToComparePattern,
            ],
            false,
        ];

        $value = 0;
        $fieldToCompareValue = '0';
        $data = [
            $pattern => $value,
            $fieldToComparePattern => $fieldToCompareValue,
        ];
        $validatorMock = $this->createAbstractValidatorMock(
            $data,
            $pattern,
            $value,
            $fieldToComparePattern,
            $fieldToCompareValue
        );
        $validatorMock->expects($this->once())
            ->method('addError')
            ->with($pattern, 'greater-than', [
                ':fieldToCompare' => $fieldToComparePattern,
                ':valueToCompare' => $fieldToCompareValue,
            ])
            ->willReturnCallback(function ($field, $ruleName) use ($pattern) {
                $this->assertEquals($pattern, $field);
                $this->assertEquals('greater-than', $ruleName);
            });

        yield 'value is numeric; value to compare is a numeric string; value is not greater than valueToCompare' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                $fieldToComparePattern,
            ],
            false,
        ];

        $value = '0';
        $fieldToCompareValue = '-1';
        $data = [
            $pattern => $value,
            $fieldToComparePattern => $fieldToCompareValue,
        ];
        $validatorMock = $this->createAbstractValidatorMock(
            $data,
            $pattern,
            $value,
            $fieldToComparePattern,
            $fieldToCompareValue
        );
        $validatorMock->expects($this->never())
            ->method('addError');

        yield 'value is "0" and value to compare is "-1"; the value is not less than valueToCompare' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                $fieldToComparePattern,
            ],
            true,
        ];

        $value = 'dagsh';
        $fieldToCompareValue = 5;
        $data = [
            $pattern => $value,
            $fieldToComparePattern => $fieldToCompareValue,
        ];
        $validatorMock = $this->createAbstractValidatorMock(
            $data,
            $pattern,
            $value,
            $fieldToComparePattern,
            $fieldToCompareValue
        );
        $validatorMock->expects($this->once())
            ->method('addError')
            ->with($pattern, 'greater-than', [
                ':fieldToCompare' => $fieldToComparePattern,
                ':valueToCompare' => $fieldToCompareValue,
            ])
            ->willReturnCallback(function ($field, $ruleName) use ($pattern) {
                $this->assertEquals($pattern, $field);
                $this->assertEquals('greater-than', $ruleName);
            });

        yield 'value is not numeric' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                $fieldToComparePattern,
            ],
            false,
        ];

        $value = 5;
        $fieldToCompareValue = 'dagsh';
        $data = [
            $pattern => $value,
            $fieldToComparePattern => $fieldToCompareValue,
        ];
        $validatorMock = $this->createAbstractValidatorMock(
            $data,
            $pattern,
            $value,
            $fieldToComparePattern,
            $fieldToCompareValue
        );
        $validatorMock->expects($this->never())
            ->method('addError');

        yield 'value is numeric; value to compare is not numeric; nothing to compare' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                $fieldToComparePattern,
            ],
            false,
        ];

        $fieldToCompareValue = '';
        $data = [
            $pattern => $value,
            $fieldToComparePattern => $fieldToCompareValue,
        ];
        $validatorMock = $this->createAbstractValidatorMock(
            $data,
            $pattern,
            $value,
            $fieldToComparePattern,
            $fieldToCompareValue
        );
        $validatorMock->expects($this->never())
            ->method('addError');

        yield 'value is numeric; value to compare is empty string; nothing to compare' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                $fieldToComparePattern,
            ],
            false,
        ];
    }

    /**
     * @dataProvider getTestedData
     */
    public function testValidate(
        AbstractValidator $validator,
        array $data,
        string $pattern,
        array $parameters,
        bool $validationResult
    ) {
        $greaterThanRule = new GreaterThan();

        $this->assertEquals(
            $validationResult,
            $greaterThanRule->validate($validator, $data, $pattern, $parameters)
        );
    }
}

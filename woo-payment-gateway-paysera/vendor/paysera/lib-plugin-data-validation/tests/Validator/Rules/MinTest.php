<?php

declare(strict_types=1);

namespace Paysera\DataValidator\Validator\Rules;

use Paysera\DataValidator\Validator\AbstractValidator;
use PHPUnit\Framework\TestCase;

class MinTest extends TestCase
{
    public function testGetName()
    {
        $minRule = new Min();

        $this->assertEquals(
            'min',
            $minRule->getName()
        );
    }

    protected function createAbstractValidatorMock(array $data, string $pattern)
    {
        $validatorMock = $this->createMock(AbstractValidator::class);
        $validatorMock->expects($this->once())
            ->method('getValues')
            ->with($data, $pattern)
            ->willReturn($data);

        return $validatorMock;
    }

    public function getTestedData(): iterable
    {
        $pattern = 'minimal_weight';
        $data = [
            $pattern => 5,
        ];
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock->expects($this->never())
            ->method('addError');

        yield 'value is correct' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                5,
            ],
            true,
        ];

        $data = [
            $pattern => 4,
        ];
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock->expects($this->once())
            ->method('addError')
            ->with($pattern, 'min', [':min' => 5, ':value' => 4])
            ->willReturnCallback(function ($field, $ruleName) use ($pattern) {
                $this->assertEquals($pattern, $field);
                $this->assertEquals('min', $ruleName);
            });

        yield 'value is less than has to be' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                5,
            ],
            false,
        ];

        $data = [];
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock->expects($this->once())
            ->method('addError')
            ->with($pattern, 'min', [':min' => 5, ':value' => null])
            ->willReturnCallback(function ($field, $ruleName) use ($pattern) {
                $this->assertEquals($pattern, $field);
                $this->assertEquals('min', $ruleName);
            });

        yield 'value is not sent' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                5,
            ],
            false,
        ];

        $data = [
            $pattern => null,
        ];
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock->expects($this->once())
            ->method('addError')
            ->with($pattern, 'min', [':min' => 5, ':value' => null])
            ->willReturnCallback(function ($field, $ruleName) use ($pattern) {
                $this->assertEquals($pattern, $field);
                $this->assertEquals('min', $ruleName);
            });

        yield 'value is null' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                5,
            ],
            false,
        ];

        $data = [
            $pattern => '0',
        ];
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock->expects($this->never())
            ->method('addError');

        yield 'value is 0' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                5,
            ],
            true,
        ];

        $data = [
            $pattern => '',
        ];
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock->expects($this->once())
            ->method('addError')
            ->with($pattern, 'min', [':min' => 5, ':value' => ''])
            ->willReturnCallback(function ($field, $ruleName) use ($pattern) {
                $this->assertEquals($pattern, $field);
                $this->assertEquals('min', $ruleName);
            });

        yield 'value is empty' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                5,
            ],
            false,
        ];

        $data = [
            $pattern => 'fewt',
        ];
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock->expects($this->once())
            ->method('addError')
            ->with($pattern, 'min', [':min' => 5, ':value' => 'fewt'])
            ->willReturnCallback(function ($field, $ruleName) use ($pattern) {
                $this->assertEquals($pattern, $field);
                $this->assertEquals('min', $ruleName);
            });

        yield 'value is not numeric' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                5,
            ],
            false,
        ];
    }

    /**
     * @dataProvider getTestedData
     */
    public function testValidate($validator, $data, $pattern, $parameters)
    {
        $rule = new Min();
        $rule->validate($validator, $data, $pattern, $parameters);
    }
}

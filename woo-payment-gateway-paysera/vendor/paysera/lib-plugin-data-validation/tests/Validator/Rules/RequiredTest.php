<?php

declare(strict_types=1);

namespace Paysera\DataValidator\Tests\Validator\Rules;

use Paysera\DataValidator\Validator\AbstractValidator;
use Paysera\DataValidator\Validator\Rules\Required;
use PHPUnit\Framework\TestCase;

class RequiredTest extends TestCase
{
    public function testGetName()
    {
        $requiredRule = new Required();

        $this->assertEquals(
            'required',
            $requiredRule->getName()
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

    public function getDataset(): iterable
    {
        $value = 'some value';
        $pattern = 'paysera_project';

        $data = [
            $pattern => $value,
        ];
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock->expects($this->never())
            ->method('addError');

        yield 'value is correct simple value' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                '',
            ],
            true,
        ];

        $value = [
            'some array value',
        ];
        $data = [
            $pattern => $value,
        ];
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock->expects($this->never())
            ->method('addError');

        yield 'value is correct array value' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                '',
            ],
            true,
        ];

        $value = '';
        $data = [
            $pattern => $value,
        ];
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock
            ->method('addError')
            ->with($pattern, 'required')
            ->willReturnCallback(function ($field, $ruleName) use ($pattern) {
                $this->assertEquals($pattern, $field);
                $this->assertEquals('required', $ruleName);
            });

        yield 'value is empty' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                '',
            ],
            false,
        ];

        $value = null;
        $data = [
            $pattern => $value,
        ];
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock
            ->method('addError')
            ->with($pattern, 'required')
            ->willReturnCallback(function ($field, $ruleName) use ($pattern) {
                $this->assertEquals($pattern, $field);
                $this->assertEquals('required', $ruleName);
            });

        yield 'value is null' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                '',
            ],
            false,
        ];

        $value = false;
        $data = [
            $pattern => $value,
        ];
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock
            ->method('addError')
            ->with($pattern, 'required')
            ->willReturnCallback(function ($field, $ruleName) use ($pattern) {
                $this->assertEquals($pattern, $field);
                $this->assertEquals('required', $ruleName);
            });

        yield 'value is false' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                '',
            ],
            false,
        ];
    }

    /**
     * @dataProvider getDataset
     */
    public function testValidate($validatorMock, $data, $pattern, $parameters)
    {
        $entityExistsRule = new Required();

        $entityExistsRule->validate($validatorMock, $data, $pattern, $parameters);
    }
}

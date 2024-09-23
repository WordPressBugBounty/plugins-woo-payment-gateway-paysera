<?php

declare(strict_types=1);

namespace Paysera\DataValidator\Tests\Validator\Rules;

use Paysera\DataValidator\Validator\AbstractValidator;
use Paysera\DataValidator\Validator\Contract\RepositoryInterface;
use Paysera\DataValidator\Validator\Rules\EntityExists;
use PHPUnit\Framework\TestCase;

class EntityExistsTest extends TestCase
{
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
        $orderStatusId = 2;
        $data = [
            'paysera_some_entity_id' => $orderStatusId,
        ];
        $pattern = 'paysera_some_entity_id';
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock->expects($this->never())
            ->method('addError');

        $repositoryMock = $this->createMock(RepositoryInterface::class);
        $repositoryMock->expects($this->once())
            ->method('find')
            ->with($orderStatusId)
            ->willReturn(['some not empty array that means that specified entity exists']);

        yield 'value is correct' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                '',
            ],
            $repositoryMock,
            true,
        ];

        $data = [
            'paysera_some_entity_id' => null,
        ];
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock->expects($this->once())
            ->method('addError')
            ->with($pattern, 'entity-exists', [':id' => null])
            ->willReturnCallback(function ($field, $ruleName) use ($pattern) {
                $this->assertEquals($pattern, $field);
                $this->assertEquals('entity-exists', $ruleName);
            });

        $repositoryMock = $this->createMock(RepositoryInterface::class);
        $repositoryMock->expects($this->never())
            ->method('find');

        yield 'value is null' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                '',
            ],
            $repositoryMock,
            false,
        ];

        $data = [
            'paysera_some_entity_id' => '',
        ];
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock->expects($this->once())
            ->method('addError')
            ->with($pattern, 'entity-exists', [':id' => ''])
            ->willReturnCallback(function ($field, $ruleName) use ($pattern) {
                $this->assertEquals($pattern, $field);
                $this->assertEquals('entity-exists', $ruleName);
            });

        $repositoryMock = $this->createMock(RepositoryInterface::class);
        $repositoryMock->expects($this->never())
            ->method('find');

        yield 'value is empty' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                '',
            ],
            $repositoryMock,
            false,
        ];

        $data = [
            'paysera_some_entity_id' => false,
        ];
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock->expects($this->once())
            ->method('addError')
            ->with($pattern, 'entity-exists', [':id' => false])
            ->willReturnCallback(function ($field, $ruleName) use ($pattern) {
                $this->assertEquals($pattern, $field);
                $this->assertEquals('entity-exists', $ruleName);
            });

        $repositoryMock = $this->createMock(RepositoryInterface::class);
        $repositoryMock->expects($this->never())
            ->method('find');

        yield 'value is false' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                '',
            ],
            $repositoryMock,
            false,
        ];

        $orderStatusId = 555;
        $data = [
            'paysera_some_entity_id' => $orderStatusId,
        ];
        $validatorMock = $this->createAbstractValidatorMock($data, $pattern);
        $validatorMock->expects($this->once())
            ->method('addError')
            ->with($pattern, 'entity-exists', [':id' => $orderStatusId])
            ->willReturnCallback(function ($field, $ruleName) use ($pattern) {
                $this->assertEquals($pattern, $field);
                $this->assertEquals('entity-exists', $ruleName);
            });

        $repositoryMock = $this->createMock(RepositoryInterface::class);
        $repositoryMock->expects($this->once())
            ->method('find')
            ->with($orderStatusId)
            ->willReturn([]);

        yield 'value correct but does not exists' => [
            $validatorMock,
            $data,
            $pattern,
            'parameters' => [
                '',
            ],
            $repositoryMock,
            false,
        ];
    }

    /**
     * @dataProvider getTestedData
     */
    public function testValidate(
        AbstractValidator $validatorMock,
        array $data,
        string $pattern,
        array $parameters,
        RepositoryInterface $repositoryMock,
        bool $validationResult
    ) {
        $entityExistsRule = new EntityExists($repositoryMock);

        $this->assertEquals(
            $validationResult,
            $entityExistsRule->validate($validatorMock, $data, $pattern, $parameters)
        );
    }
}

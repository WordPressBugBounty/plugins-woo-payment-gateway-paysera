<?php

declare(strict_types=1);

namespace Paysera\DataValidator\Validator\Contract;

interface RepositoryInterface
{
    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array;

    /**
     * @return array<array<string, mixed>>
     */
    public function findAll(): array;
}

<?php

declare(strict_types=1);

namespace Paysera\Builder;

interface TableBuilderInterface
{
    public function createTable(): void;

    public function dropTable(): void;
}

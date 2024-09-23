<?php

declare(strict_types=1);

namespace Paysera\Builder;

class DatabaseBuilder
{
    /**
     * @var TableBuilderInterface[]
     */
    private array $tableBuilders;

    /**
     * @param TableBuilderInterface[] $tableBuilders
     */
    public function __construct(array $tableBuilders)
    {
        $this->tableBuilders = $tableBuilders;
    }

    public function createTables(): void
    {
        foreach ($this->tableBuilders as $tableBuilder) {
            $tableBuilder->createTable();
        }
    }

    public function dropTables(): void
    {
        foreach ($this->tableBuilders as $tableBuilder) {
            $tableBuilder->dropTable();
        }
    }
}

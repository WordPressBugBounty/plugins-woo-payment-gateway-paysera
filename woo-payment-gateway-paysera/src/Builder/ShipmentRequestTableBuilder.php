<?php

declare(strict_types=1);

namespace Paysera\Builder;

class ShipmentRequestTableBuilder implements TableBuilderInterface
{
    private string $tableName;

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
    }

    public function createTable(): void
    {

    }

    public function dropTable(): void
    {
        global $wpdb;

        $query = sprintf('DROP TABLE IF EXISTS `%s`;', $this->tableName);

        $wpdb->query($query);
    }
}

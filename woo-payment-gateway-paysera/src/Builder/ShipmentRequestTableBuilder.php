<?php

declare(strict_types=1);

namespace Paysera\Builder;

require_once ABSPATH . 'wp-admin/includes/upgrade.php';

class ShipmentRequestTableBuilder implements TableBuilderInterface
{
    private string $tableName;

    public function __construct(string $tableName)
    {
        $this->tableName = $tableName;
    }

    public function createTable(): void
    {
        $query = sprintf(
            <<<'EOT'
            CREATE TABLE IF NOT EXISTS `%s`
            (
                id                 INT(11) NOT NULL AUTO_INCREMENT,
                order_id           VARCHAR(255) NOT NULL,
                shipping_method    VARCHAR(255) NOT NULL,
                status             VARCHAR(255) NOT NULL,
                gateway_terminal   VARCHAR(255),
                gateway_iso_code_2 VARCHAR(3),
                gateway_city       VARCHAR(255),
                house_no           VARCHAR(255),
                PRIMARY KEY (`id`),
                UNIQUE KEY unique_order_id (order_id)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
            EOT,
            $this->tableName
        );

        dbDelta($query);
    }

    public function dropTable(): void
    {
        global $wpdb;

        $query = sprintf('DROP TABLE IF EXISTS `%s`;', $this->tableName);

        $wpdb->query($query);
    }
}

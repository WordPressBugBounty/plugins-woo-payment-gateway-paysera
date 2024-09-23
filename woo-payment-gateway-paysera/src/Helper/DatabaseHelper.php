<?php

declare(strict_types=1);

namespace Paysera\Helper;

use Paysera\Builder\DatabaseBuilder;
use Paysera\Entity\PayseraDeliverySettings;
use PayseraWoocommerce;

class DatabaseHelper
{
    public const DB_VERSION_OPTION = 'PAYSERA_DB_VERSION';
    private DatabaseBuilder $databaseBuilder;

    public function __construct(DatabaseBuilder $databaseBuilder)
    {
        $this->databaseBuilder = $databaseBuilder;
    }

    public function applySchemaChanges()
    {
        $installedVersion = get_option(self::DB_VERSION_OPTION);

        if ($installedVersion === PayseraWoocommerce::PAYSERA_PLUGIN_VERSION) {
            return;
        }

        if (!$installedVersion || version_compare($installedVersion, '3.5.2', '<=')) {
            $this->migrateOldDeliveryGatewaysOptions();
        }

        if (in_array($installedVersion, ['3.5.0', '3.5.1'], true)) {
            $this->databaseBuilder->dropTables();
        }

        update_option(self::DB_VERSION_OPTION, PayseraWoocommerce::PAYSERA_PLUGIN_VERSION);
    }

    public function revertSchemaChanges(): void
    {
        delete_option(self::DB_VERSION_OPTION);
    }

    private function migrateOldDeliveryGatewaysOptions(): void
    {
        global $wpdb;

        $options = $wpdb->get_col(
            sprintf(
                'SELECT option_name FROM %1$s WHERE option_name LIKE \'%%%2$s%%\'',
                $wpdb->options,
                PayseraDeliverySettings::DELIVERY_GATEWAY_PREFIX
            ),
        );

        foreach ($options as $option) {
            $matches = [];

            preg_match(
                '/(woocommerce_paysera_delivery_[a-z]*_(?:courier|terminals))[_:](\d).*_settings/',
                $option,
                $matches
            );

            if (count($matches) === 3) {
                $newOptionName = sprintf(
                    '%s_%s_settings',
                    $matches[1],
                    $matches[2]
                );

                update_option($newOptionName, get_option($option));
            }
        }
    }
}

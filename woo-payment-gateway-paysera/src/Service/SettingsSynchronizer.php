<?php

declare(strict_types=1);

namespace Paysera\Service;

defined('ABSPATH') || exit;

use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaymentSettings;

class SettingsSynchronizer
{
    private static $syncing = false;

    public static function syncTestMode(string $testMode): void
    {
        if (self::$syncing) {
            return;
        }

        self::$syncing = true;

        $testMode = ($testMode === 'yes') ? 'yes' : 'no';

        $paymentSettings = get_option(PayseraPaymentSettings::MAIN_SETTINGS_NAME);
        if ($paymentSettings === false) {
            $paymentSettings = [];
        }
        if (!isset($paymentSettings[PayseraPaymentSettings::TEST_MODE])
            || $paymentSettings[PayseraPaymentSettings::TEST_MODE] !== $testMode
        ) {
            $paymentSettings[PayseraPaymentSettings::TEST_MODE] = $testMode;
            update_option(PayseraPaymentSettings::MAIN_SETTINGS_NAME, $paymentSettings);
        }

        $deliverySettings = get_option(PayseraDeliverySettings::SETTINGS_NAME);
        if ($deliverySettings === false) {
            $deliverySettings = [];
        }
        if (!isset($deliverySettings[PayseraDeliverySettings::TEST_MODE])
            || $deliverySettings[PayseraDeliverySettings::TEST_MODE] !== $testMode
        ) {
            $deliverySettings[PayseraDeliverySettings::TEST_MODE] = $testMode;
            update_option(PayseraDeliverySettings::SETTINGS_NAME, $deliverySettings);
        }

        self::$syncing = false;
    }
}

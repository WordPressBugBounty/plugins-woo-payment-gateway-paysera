<?php

declare(strict_types=1);

namespace Paysera\Helper;

class PayseraHTMLHelper
{
    public static function enqueueCSS(string $handle, string $path, array $deps = []): void
    {
        wp_enqueue_style($handle, $path, $deps, \PayseraWoocommerce::PAYSERA_PLUGIN_VERSION);
    }

    public static function enqueueJS(string $handle, string $path = '', array $deps = []): void
    {
        wp_enqueue_script($handle, $path, $deps, \PayseraWoocommerce::PAYSERA_PLUGIN_VERSION);
    }

    public static function registerJS(string $handle, string $path, array $deps = [], array $args = []): void
    {
        wp_register_script($handle, $path, $deps, \PayseraWoocommerce::PAYSERA_PLUGIN_VERSION, $args);
    }
}
<?php

declare(strict_types=1);

namespace Paysera\Blocks;

use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\StoreApi;

defined('ABSPATH') || exit;

/**
 * Paysera Shipping to Extend Store API.
 */
final class CheckoutStoreEndpoint
{
    /**
     * Plugin Identifier, unique to each plugin.
     *
     * @var string
     */
    public const IDENTIFIER = 'paysera';
    /**
     * Stores Rest Extending instance.
     *
     * @var ExtendRestApi
     */
    private static $extend;

    /**
     * Bootstraps the class and hooks required data.
     */
    public static function init()
    {
        self::$extend = StoreApi::container()
            ->get(ExtendSchema::class)
        ;
        self::extendStore();
    }

    /**
     * Registers the actual data into each endpoint.
     */
    public static function extendStore()
    {
        if (is_callable([self::$extend, 'register_endpoint_data'])) {
            self::$extend->register_endpoint_data(
                [
                    'endpoint' => CheckoutSchema::IDENTIFIER,
                    'namespace' => self::IDENTIFIER,
                    'schema_callback' => [self::class, 'extendCheckoutSchema'],
                    'schema_type' => ARRAY_A,
                ]
            );
        }
    }

    /**
     * Register Paysera shipping schema into the Checkout endpoint.
     *
     * @return array registered schema
     */
    public static function extendCheckoutSchema(): array
    {
        return [
            'payseraDeliveryCountry' => [
                'description' => 'Paysera Delivery terminal country',
                'type' => 'string',
                'context' => ['view', 'edit'],
                'optional' => true,
                'arg_options' => [
                    'validate_callback' => function ($value) {
                        return is_string($value);
                    },
                ],
            ],
            'payseraDeliveryCity' => [
                'description' => 'Paysera Delivery terminal city',
                'type' => 'string',
                'context' => ['view', 'edit'],
                'optional' => true,
                'arg_options' => [
                    'validate_callback' => function ($value) {
                        return is_string($value);
                    },
                ],
            ],
            'payseraDeliveryLocation' => [
                'description' => 'Paysera Delivery terminal location',
                'type' => 'string',
                'context' => ['view', 'edit'],
                'optional' => true,
                'arg_options' => [
                    'validate_callback' => function ($value) {
                        return is_string($value);
                    },
                ],
            ],
            PayseraBlock::PAYSERA_BILLING_HOUSE_NO => [
                'description' => 'Paysera Billing House No',
                'type' => 'string',
                'context' => ['view', 'edit'],
                'optional' => true,
                'arg_options' => [
                    'validate_callback' => function ($value) {
                        return $value === null || is_string($value);
                    },
                ],
            ],
            PayseraBlock::PAYSERA_SHIPPING_HOUSE_NO => [
                'description' => 'Paysera Shipping House No',
                'type' => 'string',
                'context' => ['view', 'edit'],
                'optional' => true,
                'arg_options' => [
                    'validate_callback' => function ($value) {
                        return $value === null || is_string($value);
                    },
                ],
            ],
        ];
    }
}

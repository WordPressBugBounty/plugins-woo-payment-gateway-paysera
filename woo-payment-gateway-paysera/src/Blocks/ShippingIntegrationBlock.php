<?php

declare(strict_types=1);

namespace Paysera\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Paysera\Entity\PayseraPaths;
use Paysera\Helper\PayseraDeliveryHelper;
use Paysera\Helper\PayseraHTMLHelper;
use Paysera\PayseraInit;
use Paysera\Provider\PayseraDeliverySettingsProvider;

defined('ABSPATH') || exit;

class ShippingIntegrationBlock implements IntegrationInterface
{
    private PayseraDeliverySettingsProvider $payseraDeliverySettingsProvider;
    private PayseraDeliveryHelper $payseraDeliveryHelper;

    public function __construct(
        PayseraDeliveryHelper $payseraDeliveryHelper,
        PayseraDeliverySettingsProvider $payseraDeliverySettingsProvider
    ) {
        $this->payseraDeliveryHelper = $payseraDeliveryHelper;
        $this->payseraDeliverySettingsProvider = $payseraDeliverySettingsProvider;
    }

    /**
     * @return string
     */
    public function get_name()
    {
        return 'paysera';
    }

    /**
     * When called invokes any initialization/setup for the integration.
     */
    public function initialize()
    {
        $this->registerBlockFrontendScripts();
        $this->registerBlockEditorScripts();
        $this->registerCheckoutFieldsScripts();
        $this->extendCheckoutStoreApi();
    }

    /**
     * Extends the cart schema to include the shipping-workshop value.
     */
    private function extendCheckoutStoreApi(): void
    {
        CheckoutStoreEndpoint::init();
    }

    /**
     * Returns an array of script handles to enqueue in the frontend context.
     *
     * @return string[]
     */
    public function get_script_handles()
    {
        $deliverySettings = $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings();
        $scriptHandles = [];

        if ($deliverySettings->isEnabled()) {
            $scriptHandles[] = 'paysera-shipping-block-frontend';
        }

        if ($deliverySettings->isHouseNumberFieldEnabled() === true) {
            $scriptHandles[] = 'paysera-checkout-fields';
        }

        return $scriptHandles;
    }

    /**
     * Returns an array of script handles to enqueue in the editor context.
     *
     * @return string[]
     */
    public function get_editor_script_handles()
    {
        return $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isEnabled()
            ? ['paysera-shipping-block-editor']
            : [];
    }

    /**
     * An array of key, value pairs of data made available to the block on the client side.
     *
     * @return array
     */
    public function get_script_data()
    {
        return [
            'paysera-shipping-enabled' => true,
            'shippingLogos' => $this->payseraDeliveryHelper->getShippingOptionLogoUrls(),
            'restBase' => PayseraPaths::PAYSERA_REST_BASE,
            'isHouseNoEnabled' => $this->payseraDeliverySettingsProvider
                ->getPayseraDeliverySettings()
                ->isHouseNumberFieldEnabled() === true,
        ];
    }

    public function registerBlockEditorScripts(): void
    {
        if ($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isEnabled()) {
            $scriptAssetPath = PayseraPluginPath . '/assets/build/paysera-shipping-block.asset.php';
            $script_asset = file_exists($scriptAssetPath)
                ? require $scriptAssetPath
                : [
                    'dependencies' => [],
                    'version' => PAYSERA_PLUGIN_VERSION,
                ];

            PayseraHTMLHelper::registerJS(
                'paysera-shipping-block-editor',
                PayseraPaths::PAYSERA_SHIPPING_BLOCK_JS,
                $script_asset['dependencies'],
                ['in_footer' => true]
            );

            wp_set_script_translations(
                'paysera-shipping-block-editor',
                PayseraPaths::PAYSERA_TRANSLATIONS,
                PayseraPluginPath . '/languages/'
            );
        }
    }

    public function registerBlockFrontendScripts(): void
    {
         if ($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isEnabled()) {
            $scriptAssetPath = PayseraPluginPath . '/assets/build/paysera-shipping-block-frontend.asset.php';
            $scriptAsset = file_exists($scriptAssetPath)
                ? require $scriptAssetPath
                : [
                    'dependencies' => [],
                    'version' => PAYSERA_PLUGIN_VERSION,
                ];

            PayseraHTMLHelper::registerJS(
                'paysera-shipping-block-frontend',
                PayseraPaths::PAYSERA_SHIPPING_BLOCK_FRONTEND_JS,
                array_merge($scriptAsset['dependencies'], ['wp-components']),
                ['in_footer' => true]
            );

            wp_set_script_translations(
                'paysera-shipping-block-frontend',
                PayseraPaths::PAYSERA_TRANSLATIONS,
                PayseraPluginPath . '/languages/'
            );
        }
    }

    public function registerCheckoutFieldsScripts(): void
    {
        if ($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isHouseNumberFieldEnabled() !== true) {
            return;
        }

        $scriptAssetPath = PayseraPluginPath . '/assets/build/paysera-checkout-fields.asset.php';
        $scriptAsset = file_exists($scriptAssetPath)
            ? require $scriptAssetPath
            : [
                'dependencies' => [],
                'version' => PAYSERA_PLUGIN_VERSION,
            ];

        PayseraHTMLHelper::registerJS(
            'paysera-checkout-fields',
            PayseraPaths::PAYSERA_CHECKOUT_FIELDS_BLOCK_JS,
            array_unique(array_merge(
                $scriptAsset['dependencies'],
                ['wc-blocks-checkout', 'react', 'wp-element', 'wp-i18n', 'wp-api-fetch']
            )),
            ['in_footer' => true]
        );

        wp_set_script_translations(
            'paysera-checkout-fields',
            PayseraPaths::PAYSERA_TRANSLATIONS,
            PayseraPluginPath . '/languages/'
        );
    }
}

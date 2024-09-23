<?php

declare(strict_types=1);

namespace Paysera\Blocks;

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
use Paysera\Entity\PayseraPaths;
use Paysera\Helper\PayseraDeliveryHelper;
use Paysera\Helper\SessionHelperInterface;
use Paysera\PayseraInit;
use Paysera\Provider\PayseraDeliverySettingsProvider;

defined('ABSPATH') || exit;

class ShippingIntegrationBlock implements IntegrationInterface
{
    private PayseraDeliverySettingsProvider $payseraDeliverySettingsProvider;
    private PayseraDeliveryHelper $payseraDeliveryHelper;
    private SessionHelperInterface $sessionHelper;

    public function __construct(
        PayseraDeliveryHelper $payseraDeliveryHelper,
        PayseraDeliverySettingsProvider $payseraDeliverySettingsProvider,
        SessionHelperInterface $sessionHelper
    ) {
        $this->payseraDeliveryHelper = $payseraDeliveryHelper;
        $this->payseraDeliverySettingsProvider = $payseraDeliverySettingsProvider;
        $this->sessionHelper = $sessionHelper;
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
        if (PayseraDeliveryHelper::isAvailableForDeliveryToEnqueueScripts()) {
            return ['paysera-shipping-block-frontend'];
        }

        return [];
    }

    /**
     * Returns an array of script handles to enqueue in the editor context.
     *
     * @return string[]
     */
    public function get_editor_script_handles()
    {
        return ['paysera-shipping-block-editor'];
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
            'country' => $this->sessionHelper->getData('paysera_terminal_country'),
            'city' => $this->sessionHelper->getData('paysera_terminal_city'),
            'location' => $this->sessionHelper->getData('paysera_terminal_location'),
            'shippingLogos' => $this->payseraDeliveryHelper->getShippingOptionLogoUrls(),
        ];
    }

    public function registerBlockEditorScripts(): void
    {
        $script_path = '/assets/build/paysera-shipping-block.js';
        $script_url = plugins_url($script_path, __FILE__);
        $scriptAssetPath = dirname(__FILE__) . '/assets/build/paysera-shipping-block.asset.php';
        $script_asset = file_exists($scriptAssetPath)
            ? require $scriptAssetPath
            : [
                'dependencies' => [],
                'version' => $this->getFileVersion($scriptAssetPath),
            ];

        wp_register_script(
            'paysera-shipping-block-editor',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        wp_set_script_translations(
            'paysera-shipping-block-editor',
            PayseraPaths::PAYSERA_TRANSLATIONS,
            dirname(__FILE__) . '/languages'
        );
    }

    public function registerBlockFrontendScripts(): void
    {
        $scriptUrl = PayseraPluginUrl . 'assets/build/paysera-shipping-block-frontend.js';
        $scriptAssetPath = dirname(__FILE__) . '/assets/build/paysera-shipping-block-frontend.asset.php';
        $scriptAsset = file_exists($scriptAssetPath)
            ? require $scriptAssetPath
            : [
                'dependencies' => [],
                'version' => $this->getFileVersion($scriptAssetPath),
            ];

        wp_register_script(
            'paysera-shipping-block-frontend',
            $scriptUrl,
            array_merge($scriptAsset['dependencies'], ['wp-components']),
            $scriptAsset['version'],
            true
        );

        wp_set_script_translations(
            'paysera-shipping-block-frontend',
            PayseraPaths::PAYSERA_TRANSLATIONS,
            dirname(__FILE__) . '/languages'
        );
    }

    protected function getFileVersion(string $file): string
    {
        if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG && file_exists($file)) {
            return (string) filemtime($file);
        }

        return PAYSERA_PLUGIN_VERSION;
    }
}

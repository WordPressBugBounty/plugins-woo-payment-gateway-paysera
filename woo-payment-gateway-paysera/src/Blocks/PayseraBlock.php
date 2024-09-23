<?php

declare(strict_types=1);

namespace Paysera\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Paysera\Entity\PayseraPaths;
use Paysera\Front\PayseraPaymentFrontHtml;
use Paysera\PayseraInit;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Provider\PayseraPaymentSettingsProvider;
use Paysera_Payment_Gateway;

defined('ABSPATH') || exit;

/**
 * PayseraBlock class.
 *
 * @extends AbstractPaymentMethodType
 */
final class PayseraBlock extends AbstractPaymentMethodType
{
    public const PAYSERA_BILLING_HOUSE_NO = 'payseraBillingHouseNo';
    public const PAYSERA_SHIPPING_HOUSE_NO = 'payseraShippingHouseNo';

    /**
     * Payment method name defined by payment methods extending this class.
     *
     * @var string
     */
    protected $name = 'paysera';

    /**
     * Initializes the payment method type.
     */
    public function initialize()
    {
        $payseraPaymentSettings = (new PayseraPaymentSettingsProvider())->getPayseraPaymentSettings();

        $this->setSettings(
            [
                'projectId' => $payseraPaymentSettings->getProjectId(),
                'projectPassword' => $payseraPaymentSettings->getProjectPassword(),
                'isEnabled'       => $payseraPaymentSettings->isEnabled(),
            ]
        );
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return bool
     */
    public function is_active()
    {
        if (!PayseraInit::isAvailableToEnqueueScripts()) {
            return false;
        }

        if (
            !$this->getSettings()['isEnabled']
            && !(new PayseraDeliverySettingsProvider())->getPayseraDeliverySettings()->isEnabled()
        ) {
            return false;
        }

        return !empty($this->getSettings()['projectId'])
            && !empty($this->getSettings()['projectPassword'])
        ;
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles()
    {
        $assetPath = PayseraPluginPath . '/assets/build/index.asset.php';
        $version = PAYSERA_PLUGIN_VERSION;
        $dependencies = [];

        if (file_exists($assetPath)) {
            $asset = require $assetPath;
            $version = is_array($asset) && isset($asset['version'])
                ? $asset['version']
                : $version;
            $dependencies = is_array($asset) && isset($asset['dependencies'])
                ? array_merge($asset['dependencies'], ['wp-components'])
                : $dependencies;
        }
        wp_register_script(
            'wc-paysera-blocks-integration',
            PayseraPluginUrl . '/assets/build/index.js',
            $dependencies,
            $version,
            true
        );
        wp_set_script_translations('wc-paysera-blocks-integration', 'paysera');

        $this->localizeScripts();

        wp_enqueue_style('wc-paysera-blocks-integration-css', PayseraPaths::PAYSERA_BLOCK_INTEGRATION_CSS);

        wp_enqueue_style('paysera-payment-css', PayseraPaths::PAYSERA_PAYMENT_CSS);

        return ['wc-paysera-blocks-integration'];
    }

    public function localizeScripts(): void
    {
        $paymentSettings = (new PayseraPaymentSettingsProvider())->getPayseraPaymentSettings();
        $deliverySettings = (new PayseraDeliverySettingsProvider())->getPayseraDeliverySettings();

        wp_localize_script(
            'wc-paysera-blocks-integration',
            'wcPaysera',
            [
                'restUrl' => rest_url('/'),
                'base' => PayseraPaths::PAYSERA_REST_BASE,
                'baseUrl' => rest_url(PayseraPaths::PAYSERA_REST_BASE),
                'paymentSettings' => [
                    'title' => $paymentSettings->getTitle(),
                    'description' => $paymentSettings->getDescription(),
                    'isListOfPaymentsEnabled' => $paymentSettings->isListOfPaymentsEnabled(),
                    'isTestModeEnabled' => $paymentSettings->isTestModeEnabled(),
                    'isGridViewEnabled' => $paymentSettings->isGridViewEnabled(),
                    'specificCountries' => $paymentSettings->getSpecificCountries(),
                    'isBuyerConsentEnabled' => $paymentSettings->isBuyerConsentEnabled(),
                    'buyerConsentText' => (new PayseraPaymentFrontHtml())->buildBuyerConsent(),
                    'logo' => PayseraPaths::PAYSERA_LOGO,
                ],
                'isHouseNoEnabled' => $deliverySettings->isHouseNumberFieldEnabled(),
            ]
        );
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data()
    {
        return [
            'title' => $this->getTitle(),
            'icon' => PayseraPaths::PAYSERA_LOGO,
            'supports' => $this->getSupportedFeatures(),
        ];
    }

    private function getTitle(): string
    {
        return __('Paysera Payment methods', PayseraPaths::PAYSERA_TRANSLATIONS);
    }

    private function getSupportedFeatures(): array
    {
        $gateway = new Paysera_Payment_Gateway();
        return array_filter($gateway->supports, [$gateway, 'supports']);
    }
}

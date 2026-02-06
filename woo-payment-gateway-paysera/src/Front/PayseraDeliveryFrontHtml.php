<?php

declare(strict_types=1);

namespace Paysera\Front;

defined('ABSPATH') || exit;

use Paysera\Helper\PayseraHTMLHelper;
use Paysera\Validation\PayseraDeliveryWeightValidator;
use Paysera\PayseraInit;
use WP_Error;
use Paysera\Helper\PayseraDeliveryHelper;
use Paysera\Helper\SessionHelperInterface;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Entity\PayseraPaths;
use Paysera\Entity\PayseraDeliverySettings;
use WC_Shipping_Rate;
use WC_Order;

class PayseraDeliveryFrontHtml
{
    private const WEIGHT_ERROR_MESSAGE_KEY = 'weight_error_message';
    private const DELIVERY_ASSETS_INIT_ACTION_KEY = 'paysera_enqueue_delivery_assets';

    private PayseraDeliveryHelper $payseraDeliveryHelper;
    private PayseraDeliverySettingsProvider $payseraDeliverySettingsProvider;
    private SessionHelperInterface $sessionHelper;
    private PayseraDeliveryWeightValidator $payseraDeliveryWeightValidator;

    public function __construct(
        PayseraDeliveryHelper $payseraDeliveryHelper,
        PayseraDeliverySettingsProvider $payseraDeliverySettingsProvider,
        SessionHelperInterface $sessionHelper
    ) {
        $this->payseraDeliveryHelper = $payseraDeliveryHelper;
        $this->payseraDeliverySettingsProvider = $payseraDeliverySettingsProvider;
        $this->sessionHelper = $sessionHelper;
        $this->payseraDeliveryWeightValidator = new PayseraDeliveryWeightValidator(
            $this->sessionHelper,
            $this->payseraDeliveryHelper,
            $payseraDeliverySettingsProvider
        );
    }

    public function build(): void
    {
        add_action('woocommerce_review_order_before_payment', [$this, 'showTestModeNotice']);
        add_action('woocommerce_review_order_before_payment', [$this, 'terminalLocationSelection']);
        add_action('woocommerce_checkout_process', [$this, 'checkoutFieldProcess']);
        add_action('woocommerce_after_checkout_validation', [$this, 'validateWeight'], 9999, 2);
        add_action('wp_ajax_change_paysera_method', [$this, 'changePayseraMethod']);
        add_action('wp_ajax_nopriv_change_paysera_method', [$this, 'changePayseraMethod']);
        add_action('wp_ajax_change_paysera_country', [$this, 'changePayseraCountry']);
        add_action('wp_ajax_nopriv_change_paysera_country', [$this, 'changePayseraCountry']);
        add_action('wp_ajax_change_paysera_city', [$this, 'changePayseraCity']);
        add_action('wp_ajax_nopriv_change_paysera_city', [$this, 'changePayseraCity']);
        add_action('wp_ajax_change_paysera_terminal_location', [$this, 'changePayseraTerminalLocation']);
        add_action('wp_ajax_nopriv_change_paysera_terminal_location', [$this, 'changePayseraTerminalLocation']);
        add_action('woocommerce_after_checkout_validation', [$this, 'validateTerminalLocationField'], 9999, 2);
        add_filter('woocommerce_checkout_fields', [$this, 'addRequiredHouseField']);
        add_filter('woocommerce_shipping_packages', [$this, 'setActivePayseraShippingPackageRates']);
        add_action('woocommerce_store_api_checkout_update_order_from_request', [$this, 'storeHouseNoInOrderMeta'], 9999, 2);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'removeHouseNumberFromOrderMeta'], 10, 1);
        add_filter('woocommerce_package_rates', [$this, 'filterShippingRatesByCartWeightSuitability'], 9999, 1);
        add_action(self::DELIVERY_ASSETS_INIT_ACTION_KEY, [$this, 'deliveryAssets']);
        add_action('woocommerce_blocks_enqueue_cart_block_scripts_after', [$this, 'initDeliveryAssets']);
        add_action('woocommerce_blocks_enqueue_checkout_block_scripts_after', [$this, 'initDeliveryAssets']);
        add_action('woocommerce_after_cart', [$this, 'initDeliveryAssets']);;
        add_action('woocommerce_after_checkout_form', [$this, 'initDeliveryAssets']);
    }


    public function addRequiredHouseField($fields)
    {
        if (
            $this->payseraDeliverySettingsProvider
                ->getPayseraDeliverySettings()
                ->isHouseNumberFieldEnabled() === true
        ) {
            $fields['billing'][PayseraDeliverySettings::BILLING_HOUSE_NO] = [
                'label' => __('House Number', PayseraPaths::PAYSERA_TRANSLATIONS),
                'placeholder' => __('House Number', PayseraPaths::PAYSERA_TRANSLATIONS),
                'priority' => 61,
                'required' => true,
                'clear' => true,
            ];

            $fields['shipping'][PayseraDeliverySettings::SHIPPING_HOUSE_NO] = [
                'label' => __('House Number', PayseraPaths::PAYSERA_TRANSLATIONS),
                'placeholder' => __('House Number', PayseraPaths::PAYSERA_TRANSLATIONS),
                'priority' => 61,
                'required' => true,
                'clear' => true,
            ];
        }

        return $fields;
    }

    /**
     * Validates terminal location field in old WooCommerce checkout
     * @param array $fields
     * @param WP_Error $error
     */
    public function validateTerminalLocationField(array $fields, WP_Error $error): void
    {
        if (
            empty($_POST['shipping_method'])
            || !is_array($_POST['shipping_method'])
            || !$this->payseraDeliveryHelper->isPayseraDeliveryGateway($_POST['shipping_method'][0])
        ) {
            return;
        }

        $chosenMethod = $_POST['shipping_method'][0];
        $deliveryGateway = $this->payseraDeliveryHelper->extractDeliveryGatewayDataFromShippingMethod($chosenMethod);
        $receiverType = $this->payseraDeliverySettingsProvider
            ->getPayseraDeliveryGatewaySettings($deliveryGateway['code'], $deliveryGateway['instanceId'])
            ->getReceiverType()
        ;

        if (
            ($receiverType === PayseraDeliverySettings::TYPE_PARCEL_MACHINE)
            && (
                !isset($_POST['paysera_terminal'])
                || sanitize_text_field(wp_unslash($_POST['paysera_terminal'])) === 'default'
            )
        ) {
            $error->add('validation', __('Please select the terminal location', PayseraPaths::PAYSERA_TRANSLATIONS));
        }
    }

    public function changePayseraMethod(): void
    {
        if (isset($_POST['shipping_method']) === false) {
            return;
        }

        printf(json_encode(
            $this->payseraDeliveryHelper->getFormattedCountriesByShippingMethod(
                sanitize_text_field(wp_unslash($_POST['shipping_method']))
            )
        ));

        wp_die();
    }

    public function changePayseraCountry(): void
    {
        $cities = $this->payseraDeliveryHelper->getFormattedCitiesByCountry(
            sanitize_text_field(wp_unslash($_POST['shipping_method'])),
            sanitize_text_field(wp_unslash($_POST['country']))
        );

        $data = [
            'cities' => $cities,
            'session_terminal' => [
                'country' => $this->sessionHelper->getData(PayseraDeliverySettings::TERMINAL_COUNTRY),
                'city' => $this->sessionHelper->getData(PayseraDeliverySettings::TERMINAL_CITY),
                'location' => $this->sessionHelper->getData(PayseraDeliverySettings::TERMINAL),
            ],
        ];

        printf(json_encode($data));

        wp_die();
    }

    public function changePayseraCity(): void
    {
        $terminalLocations = $this->payseraDeliveryHelper->getFormattedLocationsByCountryAndCity(
            sanitize_text_field(wp_unslash($_POST['shipping_method'])),
            sanitize_text_field(wp_unslash($_POST['country'])),
            sanitize_text_field(wp_unslash($_POST['city']))
        );

        printf(json_encode($terminalLocations));

        wp_die();
    }

    public function changePayseraTerminalLocation(): void
    {
        $this->sessionHelper->setData(
            PayseraDeliverySettings::TERMINAL_LOCATION,
            sanitize_text_field(wp_unslash($_POST['terminal']))
        );

        wp_die();
    }

    /**
     * Validates shipping method weight in old WooCommerce checkout
     * @param array $fields
     * @param WP_Error $error
     */
    public function validateWeight(array $fields, WP_Error $error): void
    {
        $result = $this->payseraDeliveryWeightValidator->validateWeight();

        if (!$result['validated']) {
            foreach ($result['messages'] as $message) {
                $error->add('validation', $message);
            }
        }
    }

    public function checkoutFieldProcess(): void
    {
        if (isset($_POST['paysera_city'])) {
            $this->sessionHelper->setData(
                PayseraDeliverySettings::TERMINAL_CITY,
                sanitize_text_field(wp_unslash($_POST['paysera_city']))
            );
        }

        if (isset($_POST['paysera_country'])) {
            $this->sessionHelper->setData(
                PayseraDeliverySettings::TERMINAL_COUNTRY,
                sanitize_text_field(wp_unslash($_POST['paysera_country']))
            );
        }

        if (isset($_POST['paysera_terminal'])) {
            $this->sessionHelper->setData(
                PayseraDeliverySettings::TERMINAL,
                sanitize_text_field(wp_unslash($_POST['paysera_terminal']))
            );
        }

        if ($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isHouseNumberFieldEnabled()) {
            if (isset($_POST[PayseraDeliverySettings::BILLING_HOUSE_NO])) {
                $this->sessionHelper->setData(
                    PayseraDeliverySettings::BILLING_HOUSE_NO,
                    sanitize_text_field(wp_unslash($_POST[PayseraDeliverySettings::BILLING_HOUSE_NO]))
                );
            }

            if (isset($_POST[PayseraDeliverySettings::SHIPPING_HOUSE_NO])) {
                $this->sessionHelper->setData(
                    PayseraDeliverySettings::SHIPPING_HOUSE_NO,
                    sanitize_text_field(wp_unslash($_POST[PayseraDeliverySettings::SHIPPING_HOUSE_NO]))
                );
            }
        } else {
            $this->sessionHelper->setData(PayseraDeliverySettings::BILLING_HOUSE_NO, null);
            $this->sessionHelper->setData(PayseraDeliverySettings::SHIPPING_HOUSE_NO, null);
        }
    }

    public function initDeliveryAssets(): void
    {
        if (!did_action(self::DELIVERY_ASSETS_INIT_ACTION_KEY)) {
            do_action(self::DELIVERY_ASSETS_INIT_ACTION_KEY);
        }
    }

    public function deliveryAssets(): void
    {
        if ($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isEnabled()) {
            PayseraHTMLHelper::enqueueCSS('paysera-select-2-css', PayseraPaths::PAYSERA_SELECT_2_CSS);
            PayseraHTMLHelper::enqueueJS('paysera-select-2-js', PayseraPaths::PAYSERA_SELECT_2_JS, ['jquery']);

            PayseraHTMLHelper::enqueueJS('paysera-cart-logos-js', PayseraPaths::PAYSERA_DELIVERY_CART_LOGOS_JS);

            wp_localize_script(
                'paysera-cart-logos-js',
                'data',
                [
                    'shippingLogos' => $this->payseraDeliveryHelper->getShippingOptionLogoUrls(),
                ]
            );

            PayseraHTMLHelper::enqueueJS('paysera-delivery-frontend-js', PayseraPaths::PAYSERA_DELIVERY_FRONTEND_JS, ['jquery']);

            wp_localize_script(
                'paysera-delivery-frontend-js',
                'payseraDeliveryFrontEndData',
                [
                    'isTestModeEnabled' => $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isTestModeEnabled()
                ]
            );

            PayseraHTMLHelper::registerJS(
                'paysera-delivery-frontend-ajax-js',
                PayseraPaths::PAYSERA_DELIVERY_FRONTEND_AJAX_JS,
                [],
                ['in_footer' => true]
            );
            PayseraHTMLHelper::enqueueJS('paysera-delivery-frontend-ajax-js');
            wp_localize_script(
                'paysera-delivery-frontend-ajax-js',
                'ajax_object',
                ['ajaxurl' => admin_url('admin-ajax.php')]
            );

            PayseraHTMLHelper::enqueueCSS('paysera-shipping-block-frontend-css', PayseraPluginUrl . 'assets/build/style-paysera-shipping-block-frontend.css');

            if ($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isGridViewEnabled() === true) {
                PayseraHTMLHelper::enqueueCSS('paysera-delivery-grid-css', PayseraPaths::PAYSERA_DELIVERY_GRID_CSS);
                PayseraHTMLHelper::enqueueJS(
                    'paysera-delivery-frontend-grid-js',
                    PayseraPaths::PAYSERA_DELIVERY_FRONTEND_GRID_JS,
                    ['jquery']
                );
            }

            $scriptAssetPath = PayseraPaths::PAYSERA_DELIVERY_CART_VALIDATION_ASSETS;
            $scriptAsset = file_exists($scriptAssetPath)
                ? require $scriptAssetPath
                : [
                    'dependencies' => [],
                    'version' => PAYSERA_PLUGIN_VERSION,
                ];
            PayseraHTMLHelper::registerJS(
                'paysera-cart-shipping-validation',
                PayseraPaths::PAYSERA_DELIVERY_CART_VALIDATION_JS,
                array_merge($scriptAsset['dependencies'], ['wp-components']),
                ['in_footer' => true]
            );

            wp_set_script_translations(
                'paysera-cart-shipping-validation',
                PayseraPaths::PAYSERA_TRANSLATIONS,
                PayseraPluginPath . '/languages/'
            );
            PayseraHTMLHelper::enqueueJS('paysera-cart-shipping-validation');
            PayseraHTMLHelper::enqueueJS(
                'paysera-cart-shipping-selector',
                PayseraPaths::PAYSERA_DELIVERY_SELECTOR_JS
            );
            wp_localize_script(
                'paysera-cart-shipping-selector',
                'data',
                [
                    'weightRestrictionHints' => $this->sessionHelper->getData(
                        PayseraInit::DELIVERY_GATEWAY_WEIGHT_HINT_SESSION_KEY
                    ),
                ]
            );
        }
    }

    public function terminalLocationSelection(): void
    {
        if ($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isEnabled()) {
            PayseraHTMLHelper::enqueueCSS('paysera-delivery-css', PayseraPaths::PAYSERA_DELIVERY_CSS, ['wc-components']);

            if ($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isGridViewEnabled() === true) {
                PayseraHTMLHelper::enqueueCSS('paysera-delivery-grid-css', PayseraPaths::PAYSERA_DELIVERY_GRID_CSS);
                PayseraHTMLHelper::enqueueJS(
                    'paysera-delivery-frontend-grid-js',
                    PayseraPaths::PAYSERA_DELIVERY_FRONTEND_GRID_JS,
                    ['jquery']
                );
            }

            printf(
                $this->createSelectField(
                    'paysera-delivery-terminal-country',
                    'Terminal country',
                    'paysera_country',
                    'Please select the country'
                )
                . $this->createSelectField(
                    'paysera-delivery-terminal-city',
                    'Terminal city',
                    'paysera_city',
                    'Please select the city/municipality'
                )
                . $this->createSelectField(
                    'paysera-delivery-terminal-location',
                    'Terminal location',
                    'paysera_terminal',
                    'Please select the terminal location'
                )
            );
        }
    }

    private function createSelectField(
        string $className,
        string $label,
        string $selectionName,
        string $defaultOption
    ): string {
        return '<div class="' . $className . ' paysera-delivery-terminal">' . '<span>'
            . __($label, PayseraPaths::PAYSERA_TRANSLATIONS) . ' <span class="paysera-delivery-required">*</span></span>'
            . '<select class="' . $className . '-selection" name="' . $selectionName . '">' . '<option value="default">'
            . __($defaultOption, PayseraPaths::PAYSERA_TRANSLATIONS) . '</option>' . '</select></div>';
    }

    public function setActivePayseraShippingPackageRates($packages)
    {
        $options = get_option(PayseraDeliverySettings::DELIVERY_GATEWAYS_SETTINGS_NAME);

        foreach ($packages as $index => $package) {
            $packages[$index]['rates'] = $this->getActiveShippingPackageRates(
                $package['rates'],
                empty($options) === true ? [] : $options
            );
        }

        return $packages;
    }

    private function getActiveShippingPackageRates(array $packageRates, array $options): array
    {
        $activePackageRates = [];
        foreach ($packageRates as $packageRate) {
            $packageRateId = $packageRate->get_id();

            if ($this->payseraDeliveryHelper->isPayseraDeliveryGateway($packageRateId)) {
                if (!$this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isEnabled()) {
                    continue;
                }
                $deliveryGateway = str_replace(PayseraDeliverySettings::DELIVERY_GATEWAY_PREFIX, '', $packageRateId);
                $deliveryGateway = strtok($deliveryGateway, '_');

                if (
                    isset($options[PayseraDeliverySettings::DELIVERY_GATEWAYS][$deliveryGateway])
                    && $options[PayseraDeliverySettings::DELIVERY_GATEWAYS][$deliveryGateway]
                ) {
                    $activePackageRates[$packageRateId] = $packageRate;
                }
            } else {
                $activePackageRates[$packageRateId] = $packageRate;
            }
        }

        return $activePackageRates;
    }

    public function storeHouseNoInOrderMeta($order): void
    {
        if (!$this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isHouseNumberFieldEnabled()) {
            $this->sessionHelper->setData(PayseraDeliverySettings::BILLING_HOUSE_NO, null);
            $this->sessionHelper->setData(PayseraDeliverySettings::SHIPPING_HOUSE_NO, null);
            return;
        }

        $billingHouseNo = sanitize_text_field(
            (string) $this->sessionHelper->getData(PayseraDeliverySettings::BILLING_HOUSE_NO)
        );
        $shippingHouseNo = sanitize_text_field(
            (string) $this->sessionHelper->getData(PayseraDeliverySettings::SHIPPING_HOUSE_NO)
        );

        if ($billingHouseNo !== '') {
            $order->update_meta_data(
                PayseraDeliverySettings::ORDER_META_KEY_BILLING_HOUSE_NO,
                $billingHouseNo
            );
        }

        if ($shippingHouseNo !== '') {
            $order->update_meta_data(
                PayseraDeliverySettings::ORDER_META_KEY_SHIPPING_HOUSE_NO,
                $shippingHouseNo
            );
        }
    }

    /**
     * Remove house number from order meta when field is disabled
     * This prevents WooCommerce from automatically saving cached values
     *
     * @param int $order_id
     */
    public function removeHouseNumberFromOrderMeta($order_id)
    {
        if ($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isHouseNumberFieldEnabled() === true) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            return;
        }

        $is_checkout = defined('WOOCOMMERCE_CHECKOUT') && WOOCOMMERCE_CHECKOUT;
        $can_edit = current_user_can('edit_shop_order', $order_id);

        if (!$is_checkout && !$can_edit) {
            error_log(sprintf(
                'Security: Unauthorized attempt to modify order meta. User ID: %d, Order ID: %d',
                get_current_user_id(),
                $order_id
            ));
            return;
        }

        $order->delete_meta_data(PayseraDeliverySettings::ORDER_META_KEY_BILLING_HOUSE_NO);
        $order->delete_meta_data(PayseraDeliverySettings::ORDER_META_KEY_SHIPPING_HOUSE_NO);
        $order->save();
    }

    /**
     * @param array<WC_Shipping_Rate>|mixed $rates Shipping rates array or any value from third-party filters
     * @param array<string, mixed> $package Package data from WooCommerce
     *
     * @return array<string, WC_Shipping_Rate>|mixed Returns filtered rates array when input is array,
     *                                                otherwise returns input unchanged for compatibility
     */
    public function filterShippingRatesByCartWeightSuitability($rates, $package = [])
    {
        if (!is_array($rates)) {
            return $rates;
        }

        $hideUnsuitableShippingMethods = $this->payseraDeliverySettingsProvider
            ->getPayseraDeliverySettings()
            ->isHideShippingMethodsEnabled()
        ;

        return array_filter($rates, function ($rate) use ($hideUnsuitableShippingMethods) {
            return (
                $this->isThirdPartyShippingRate($rate)
                || $this->isShippingRateValidForCartWeight($rate)
                || $hideUnsuitableShippingMethods === false
            );
        });
    }

    private function isShippingRateValidForCartWeight(WC_Shipping_Rate $shippingRate): bool
    {
        $validationResult = $this->payseraDeliveryWeightValidator
            ->validateShippingMethod(
                $this->payseraDeliveryHelper->getCartTotalDeliveryWeight(),
                $shippingRate->get_id(),
            )
        ;

        if ($validationResult['validated'] === false) {
            $shippingRate->add_meta_data(
                self::WEIGHT_ERROR_MESSAGE_KEY,
                current($validationResult['messages'])
            );
        }

        return $validationResult['validated'];
    }

    public function showTestModeNotice(): void
    {
        if (wp_doing_ajax()) {
            return;
        }

        $payseraDeliverySettings = $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings();
        if (!$payseraDeliverySettings->isEnabled() || !$payseraDeliverySettings->isTestModeEnabled()) {
            return;
        }

        printf(
            '<div style="color: red; display: none" class="paysera-delivery-testmode-notice">%s</div>',
            __(
                "Paysera delivery plugin is in test mode — delivery orders are simulated, but payments will be charged as you're using an external (non-Paysera) payment gateway.",
                PayseraPaths::PAYSERA_TRANSLATIONS
            )
        );
    }

    private function isThirdPartyShippingRate(WC_Shipping_Rate $shippingRate): bool
    {
        return strpos($shippingRate->get_id(), 'paysera_delivery_') === false;
    }
}

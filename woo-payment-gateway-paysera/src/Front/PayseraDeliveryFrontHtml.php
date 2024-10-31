<?php

declare(strict_types=1);

namespace Paysera\Front;

defined('ABSPATH') || exit;

use Paysera\Validation\PayseraDeliveryWeightValidator;
use WP_Error;
use Paysera\Helper\PayseraDeliveryHelper;
use Paysera\Helper\SessionHelperInterface;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Entity\PayseraPaths;
use Paysera\Entity\PayseraDeliverySettings;
use WC_Shipping_Rate;

class PayseraDeliveryFrontHtml
{
    private const WEIGHT_ERROR_MESSAGE_KEY = 'weight_error_message';

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
        add_filter('woocommerce_package_rates', [$this, 'filterShippingRatesByCartWeightSuitability'], 9999, 1);
    }


    public function addRequiredHouseField(array $fields): array
    {
        if (
            $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isHouseNumberFieldEnabled()
            === true
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
    }

    public function terminalLocationSelection(): void
    {
        if (PayseraDeliveryHelper::isAvailableForDeliveryToEnqueueScripts()) {
            wp_enqueue_style('paysera-delivery-css', PayseraPaths::PAYSERA_DELIVERY_CSS, ['wc-components']);

            if ($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isGridViewEnabled() === true) {
                wp_enqueue_style('paysera-delivery-grid-css', PayseraPaths::PAYSERA_DELIVERY_GRID_CSS);
                wp_enqueue_script(
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

    public function setActivePayseraShippingPackageRates(array $packages): array
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

    public function storeHouseNoInOrderMeta(\WC_Order $order): void
    {
        if ($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isHouseNumberFieldEnabled() === false) {
            return;
        }

        $billingHouseNo = $this->sessionHelper->getData(PayseraDeliverySettings::BILLING_HOUSE_NO);
        $shippingHouseNo = $this->sessionHelper->getData(PayseraDeliverySettings::SHIPPING_HOUSE_NO);

        if (!empty($billingHouseNo)) {
            $order->update_meta_data('_billing_house_no', $billingHouseNo);
        }

        if (!empty($shippingHouseNo)) {
            $order->update_meta_data('_shipping_house_no', $shippingHouseNo);
        }
    }

    /**
     * @param array<WC_Shipping_Rate> $rates
     *
     * @return array
     */
    public function filterShippingRatesByCartWeightSuitability(array $rates): array
    {
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

    private function isThirdPartyShippingRate(WC_Shipping_Rate $shippingRate): bool
    {
        return strpos($shippingRate->get_id(), 'paysera_delivery_') === false;
    }
}

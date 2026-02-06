<?php

declare(strict_types=1);

namespace Paysera\Admin;

defined('ABSPATH') || exit;

use Paysera\Action\PayseraDeliveryActions;
use Paysera\Action\PayseraSelfDiagnosisActions;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliverySettingsInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Service\DeliveryLoggerInterface;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\Helper\EventHandlingHelper;
use Paysera\Helper\LogHelper;
use Paysera\Helper\PayseraDeliveryLibraryHelper;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Service\SettingsSynchronizer;
use Paysera\Service\WordPressContextInterface;
use WC_Order;

class PayseraDeliveryAdmin
{
    public const TAB_GENERAL_SETTINGS = 'general_settings';
    public const TAB_EXTRA_SETTINGS = 'extra_settings';
    public const TAB_DELIVERY_GATEWAYS_LIST_SETTINGS = 'delivery_gateways_list_settings';

    private PayseraAdminHtml $adminHtml;
    private PayseraDeliveryAdminHtml $deliveryAdminHtml;
    private PayseraDeliveryLibraryHelper $deliveryLibraryHelper;
    private PayseraDeliveryActions $deliveryActions;
    private PayseraDeliverySettingsProvider $deliverySettingsProvider;
    private DeliveryLoggerInterface $logger;
    private EventHandlingHelper $eventHandlingHelper;
    private WordPressContextInterface $wordPressContext;
    private ?PayseraDeliverySettingsInterface $deliverySettings;
    private string $tab;
    /**
     * @var string[]
     */
    private array $tabs;

    public function __construct(
        PayseraAdminHtml $adminHtml,
        PayseraDeliveryAdminHtml $deliveryAdminHtml,
        PayseraDeliveryActions $deliveryActions,
        PayseraDeliveryLibraryHelper $deliveryLibraryHelper,
        DeliveryLoggerInterface $logger,
        PayseraDeliverySettingsProvider $deliverySettingsProvider,
        EventHandlingHelper $eventHandlingHelper,
        WordPressContextInterface $wordPressContext
    ) {
        $this->adminHtml = $adminHtml;
        $this->deliveryLibraryHelper = $deliveryLibraryHelper;
        $this->deliveryActions = $deliveryActions;
        $this->deliveryAdminHtml = $deliveryAdminHtml;
        $this->deliverySettingsProvider = $deliverySettingsProvider;
        $this->wordPressContext = $wordPressContext;
        $this->deliverySettings = null;
        $this->tab = self::TAB_GENERAL_SETTINGS;
        $this->tabs = [
            self::TAB_GENERAL_SETTINGS,
            self::TAB_EXTRA_SETTINGS,
            self::TAB_DELIVERY_GATEWAYS_LIST_SETTINGS,
        ];
        $this->logger = $logger;
        $this->eventHandlingHelper = $eventHandlingHelper;
    }

    public function build(): void
    {
        add_action(
            'admin_init',
            [$this, 'settingsInit']
        );
        add_action(
            'woocommerce_checkout_order_processed',
            [$this, 'wcCheckoutOrderProcessed'],
            1,
            3
        );
        add_action(
            'woocommerce_store_api_checkout_order_processed',
            [$this, 'wcStoreApiCheckoutOrderProcessed'],
            100,
            1
        );
        add_filter(
                'woocommerce_admin_shipping_fields',
                [$this, 'addHouseNumberFieldOnOrderEdit']
        );
        add_filter(
                'woocommerce_order_formatted_shipping_address',
                [$this, 'addShippingHouseNumberFieldAdminOrderPreview'],
                10,
                2
        );
        add_filter(
                'woocommerce_formatted_address_replacements',
                [$this, 'buildAddressReplacements'],
                10,
                2
        );
        add_filter(
                'woocommerce_localisation_address_formats',
                [$this, 'addHouseNumberToAddressFormat'],
                10
        );
        add_action(
            'woocommerce_product_options_dimensions',
            [$this, 'appendDimensionsHint']
        );
        add_action(
            'woocommerce_after_order_itemmeta',
            [$this, 'displayProductDimensionsAndWeight'],
            10,
            3
        );
    }

    public function settingsInit(): void
    {
        $this->ensureDeliverySettingsLoaded();

        if (array_key_exists('tab', $_GET) === true) {
            $this->tab = sanitize_text_field(wp_unslash($_GET['tab']));
        }

        if (in_array($this->tab, $this->tabs, true) === false) {
            $this->tab = self::TAB_GENERAL_SETTINGS;
        }

        add_settings_section(
            self::TAB_GENERAL_SETTINGS,
            null,
            [$this, 'payseraDeliverySettingsSectionCallback'],
            PayseraDeliverySettings::SETTINGS_NAME
        );
        add_settings_section(
            self::TAB_EXTRA_SETTINGS,
            null,
            [$this, 'payseraDeliverySettingsSectionCallback'],
            PayseraDeliverySettings::EXTRA_SETTINGS_NAME
        );

        register_setting(
            PayseraDeliverySettings::SETTINGS_NAME,
            PayseraDeliverySettings::SETTINGS_NAME,
            [
                'sanitize_callback' => function ($value) {
                    if (isset($value[PayseraDeliverySettings::TEST_MODE])) {
                        SettingsSynchronizer::syncTestMode($value[PayseraDeliverySettings::TEST_MODE]);
                    }
                    return $value;
                },
            ]
        );
        register_setting(PayseraDeliverySettings::EXTRA_SETTINGS_NAME, PayseraDeliverySettings::EXTRA_SETTINGS_NAME);

        if ($this->tab === self::TAB_GENERAL_SETTINGS) {
            add_settings_field(
                PayseraDeliverySettings::ENABLED,
                __('Enable', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'enableRender'],
                PayseraDeliverySettings::SETTINGS_NAME,
                self::TAB_GENERAL_SETTINGS
            );
            add_settings_field(
                PayseraDeliverySettings::PROJECT_ID,
                __('Project ID', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'projectIdRender'],
                PayseraDeliverySettings::SETTINGS_NAME,
                self::TAB_GENERAL_SETTINGS
            );
            add_settings_field(
                PayseraDeliverySettings::PROJECT_PASSWORD,
                __('Project password', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'projectPasswordRender'],
                PayseraDeliverySettings::SETTINGS_NAME,
                self::TAB_GENERAL_SETTINGS
            );
            add_settings_field(
                PayseraDeliverySettings::TEST_MODE,
                __('Test mode', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'testModeRender'],
                PayseraDeliverySettings::SETTINGS_NAME,
                self::TAB_GENERAL_SETTINGS
            );
            add_settings_field(
                PayseraDeliverySettings::HOUSE_NUMBER_FIELD,
                __('House number field', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'houseNumberFieldRender'],
                PayseraDeliverySettings::SETTINGS_NAME,
                self::TAB_GENERAL_SETTINGS
            );
        } elseif ($this->tab === self::TAB_EXTRA_SETTINGS) {
            add_settings_field(
                PayseraDeliverySettings::GRID_VIEW,
                __('Grid view', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'gridViewRender'],
                PayseraDeliverySettings::EXTRA_SETTINGS_NAME,
                self::TAB_EXTRA_SETTINGS
            );
            add_settings_field(
                PayseraDeliverySettings::HIDE_SHIPPING_METHODS,
                __('Hide shipping methods', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'hideShippingMethodsRender'],
                PayseraDeliverySettings::EXTRA_SETTINGS_NAME,
                self::TAB_EXTRA_SETTINGS
            );
            add_settings_field(
                PayseraDeliverySettings::LOG_LEVEL,
                __('Log Level', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'logLevelOptionsRender'],
                PayseraDeliverySettings::EXTRA_SETTINGS_NAME,
                self::TAB_EXTRA_SETTINGS
            );
        } elseif ($this->tab === self::TAB_DELIVERY_GATEWAYS_LIST_SETTINGS) {
            add_settings_field(
                PayseraDeliverySettings::DELIVERY_GATEWAYS,
                __('Delivery Gateways', PayseraPaths::PAYSERA_TRANSLATIONS),
                [$this, 'buildDeliveryGatewaysList'],
                PayseraDeliverySettings::SETTINGS_NAME,
                self::TAB_GENERAL_SETTINGS
            );
        }
    }

    public function buildSettingsPage(): void
    {
        $this->ensureDeliverySettingsLoaded();

        if (isset($_REQUEST['settings-updated'])) {
            printf($this->adminHtml->getSettingsSavedMessage());
        }

        if (
            (
                empty($this->deliverySettings->getProjectId())
                || empty($this->deliverySettings->getProjectPassword())
            )
            && isset($_REQUEST['enabled_massage'])
            && sanitize_text_field(wp_unslash($_REQUEST['enabled_massage'])) === 'yes'
        ) {
            printf($this->adminHtml->getSettingsWarningNotice());
        }

        if (
            !isset($_REQUEST['settings-updated'])
            && isset($_REQUEST['invalid-credentials'])
            && sanitize_text_field(wp_unslash($_REQUEST['invalid-credentials'])) === 'yes'
        ) {
            printf($this->adminHtml->getSettingsInvalidCredentialsNotice());
        }

        if (
            !isset($_REQUEST['settings-updated'])
            && isset($_REQUEST[PayseraSelfDiagnosisActions::COMPATIBILITY_CHECK_FAILED_KEY])
            && sanitize_text_field(wp_unslash($_REQUEST[PayseraSelfDiagnosisActions::COMPATIBILITY_CHECK_FAILED_KEY])) === PayseraSelfDiagnosisActions::VALUE_ENABLED
        ) {
            printf($this->adminHtml->getSettingsCompatibilityValidationErrorNotice());
        }

        $this->deliveryAdminHtml->buildDeliverySettings(
            isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $this->tab,
            $this->deliverySettings->getProjectId()
        );
    }

    public function payseraDeliverySettingsSectionCallback(): void
    {
    }

    public function enableRender(): void
    {
        printf(
            $this->deliveryAdminHtml->enablePayseraDeliveryHtml(
                $this->deliverySettings->isEnabled()
            )
        );
    }

    public function projectIdRender(): void
    {
        printf(
            $this->adminHtml->getNumberInput(),
            esc_attr(PayseraDeliverySettings::SETTINGS_NAME . '[' . PayseraDeliverySettings::PROJECT_ID . ']'),
            esc_attr($this->deliverySettings->getProjectId()),
            0
        );
    }

    public function projectPasswordRender(): void
    {
        printf(
            $this->adminHtml->getTextInput(),
            esc_attr(PayseraDeliverySettings::SETTINGS_NAME . '[' . PayseraDeliverySettings::PROJECT_PASSWORD . ']'),
            esc_attr($this->deliverySettings->getProjectPassword())
        );
    }

    public function testModeRender(): void
    {
        printf(
            $this->adminHtml->getEnableInput(
                PayseraDeliverySettings::SETTINGS_NAME . '[' . PayseraDeliverySettings::TEST_MODE . ']',
                $this->deliverySettings->isTestModeEnabled()
                    ? 'yes' : 'no'
            ) .
            $this->adminHtml->buildLabel(
                __('A test delivery order and test payment will be created.', PayseraPaths::PAYSERA_TRANSLATIONS)
            )
        );
    }

    public function houseNumberFieldRender(): void
    {
        printf(
            $this->adminHtml->getEnableInput(
                PayseraDeliverySettings::SETTINGS_NAME . '[' . PayseraDeliverySettings::HOUSE_NUMBER_FIELD . ']',
                $this->deliverySettings
                    ->isHouseNumberFieldEnabled() ? 'yes' : 'no'
            )
        );
    }

    public function gridViewRender(): void
    {
        printf(
            $this->adminHtml->getEnableInput(
                PayseraDeliverySettings::EXTRA_SETTINGS_NAME . '[' . PayseraDeliverySettings::GRID_VIEW . ']',
                $this->deliverySettings->isGridViewEnabled()
                    ? 'yes' : 'no'
            )
        );
    }

    public function hideShippingMethodsRender(): void
    {
        printf(
            $this->adminHtml->getEnableInput(
                PayseraDeliverySettings::EXTRA_SETTINGS_NAME . '[' . PayseraDeliverySettings::HIDE_SHIPPING_METHODS . ']',
                $this->deliverySettings->isHideShippingMethodsEnabled()
                    ? 'yes' : 'no'
            ) .
            $this->adminHtml->buildLabel(
                __(
                    'Hide shipping methods that are above or under set weight limits',
                    PayseraPaths::PAYSERA_TRANSLATIONS
                )
            )
        );
    }

    public function logLevelOptionsRender(): void
    {
        $logHelper = new LogHelper();

        printf(
            $this->adminHtml->getLogLevelHtml(
                PayseraDeliverySettings::EXTRA_SETTINGS_NAME . '[' . PayseraDeliverySettings::LOG_LEVEL . ']',
                $this->deliverySettings->getLogLevel(),
                LogHelper::LOGGER_TYPE_DELIVERY,
                $logHelper
            )
        );
    }

    public function buildDeliveryGatewaysList(): void
    {
        $deliveryGateways = $this->deliveryLibraryHelper->getPayseraDeliveryGateways();
        $this->deliveryActions->setDeliveryGatewayTitles($deliveryGateways);
        $this->deliveryActions->reSyncDeliveryGatewayStatus($deliveryGateways);
        $this->deliveryActions->syncShipmentMethodsStatus(
            $this->deliveryLibraryHelper->getPayseraShipmentMethods()
        );

        if (empty($deliveryGateways) === false) {
            printf(
                $this->deliveryAdminHtml->buildDeliveryGatewaysHtml(
                    $deliveryGateways,
                    get_option(PayseraDeliverySettings::DELIVERY_GATEWAYS_SETTINGS_NAME)
                )
            );
        } else {
            printf(
                sprintf(
                    "<strong style='%s'>%s</strong>",
                    'color: red',
                    __('Either project id or project password is incorrect', PayseraPaths::PAYSERA_TRANSLATIONS)
                )
            );
        }
    }

    public function appendDimensionsHint(): void
    {
        if (!wc_product_dimensions_enabled()) {
            return;
        }

        if (count($this->deliverySettingsProvider->getActivePayseraDeliveryGateways()) <= 0) {
            return;
        } ?>
        <p class="form-field _weight_field" style="margin-left: -12px; color: gray;">
            <label style="margin-left: 0;"></label>
            <span class="woocommerce-help-tip"></span>
            <span>
                    <?php
                    esc_html_e(
                        'Please provide package dimensions if you would like to use the shipping methods provided by Paysera.',
                        PayseraPaths::PAYSERA_TRANSLATIONS
                    ); ?>
                </span>
        </p>
        <?php
    }

    public function displayProductDimensionsAndWeight($itemId, $item, $product): void
    {
        if (!is_admin() || !$product || !$product->exists()) {
            return;
        }

        $this->ensureDeliverySettingsLoaded();

        if (
            !$this->deliverySettings->isEnabled()
            || count($this->deliverySettingsProvider->getActivePayseraDeliveryGateways()) === 0
        ) {
            return;
        }

        $info = $this->buildDimensionsInfo($product);

        if (count($info) > 0) {
            echo implode('', $info);
        }
    }

    private function buildDimensionsInfo($product): array
    {
        $fields = [
            'weight' => [
                'value' => $product->get_weight(),
                'label' => __('Weight', 'woocommerce'),
                'unit' => get_option('woocommerce_weight_unit'),
            ],
            'dimensions' => [
                'value' => implode(' x ', array_filter([
                    $product->get_length(),
                    $product->get_width(),
                    $product->get_height(),
                ])),
                'label' => __('Dimensions', 'woocommerce'),
                'unit' => get_option('woocommerce_dimension_unit'),
            ],
        ];

        $info = [];
        foreach ($fields as $field) {
            if (isset($field['value']) && $field['value'] !== '') {
                $info[] = sprintf(
                    '<div class="wc-order-item-variation"><strong>%s:</strong> %s %s</div>',
                    esc_html($field['label']),
                    esc_html($field['value']),
                    esc_html($field['unit'])
                );
            }
        }

        return $info;
    }



    public function wcCheckoutOrderProcessed(int $orderId, array $postedData, WC_Order $order): void
    {
        $this->logger->info(sprintf('Processing checkout order for order id %d.', $order->get_id()));

        $this->triggerWCOrderCreated($order);
    }

    public function wcStoreApiCheckoutOrderProcessed(WC_Order $order): void
    {
        $this->logger->info(sprintf('Processing store API checkout order for order id %d.', $order->get_id()));

        $this->triggerWCOrderCreated($order);
    }

    private function triggerWCOrderCreated(WC_Order $order)
    {
        $this->eventHandlingHelper->handle(PayseraDeliverySettings::WC_ORDER_EVENT_CREATED, ['order' => $order]);
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    public function addHouseNumberFieldOnOrderEdit($fields)
    {
        if ($this->isHouseNumberFieldEnabled()) {
            $fields['house_no'] = [
                    'label' => __('House Number', PayseraPaths::PAYSERA_TRANSLATIONS),
                    'show' => false,
            ];
        }

        return $fields;
    }

    /**
     * @param array<string, string> $formats
     * @return array<string, string>
     */
    public function addHouseNumberToAddressFormat($formats)
    {
        if (!$this->wordPressContext->isAdmin()) {
            return $formats;
        }

        if ($this->isHouseNumberFieldEnabled()) {
            foreach ($formats as $country => $format) {
                $formats[$country] .= "\n{house_no}";
            }
        }
        return $formats;
    }

    /**
     * @param array<string, string> $replacements
     * @param array<string, mixed> $args
     * @return array<string, string>
     */
    public function buildAddressReplacements($replacements, $args)
    {
        if (!$this->wordPressContext->isAdmin()) {
            return $replacements;
        }

        if ($this->isHouseNumberFieldEnabled()) {
            $houseNumber = isset($args['house_no'])
                ? esc_html(sanitize_text_field((string) $args['house_no']))
                : '';
            $replacements['{house_no}'] = $houseNumber !== ''
                ? (__('House Number', PayseraPaths::PAYSERA_TRANSLATIONS) . ': ' . $houseNumber)
                : '';
        }

        return $replacements;
    }

    /**
     * @param array<string, mixed> $address
     * @param WC_Order $order
     * @return array<string, mixed>
     */
    public function addShippingHouseNumberFieldAdminOrderPreview($address, $order)
    {
        if (!$this->wordPressContext->isAdmin()) {
            return $address;
        }

        if ($this->isHouseNumberFieldEnabled()) {
            $house_number = $order->get_meta('_shipping_house_no');
            if ($house_number !== '' && $house_number !== null) {
                $address['house_no'] = sanitize_text_field((string) $house_number);
            }
        }

        return $address;
    }

    private function isHouseNumberFieldEnabled(): bool
    {
        $this->ensureDeliverySettingsLoaded();
        return $this->deliverySettings->isHouseNumberFieldEnabled();
    }

    private function ensureDeliverySettingsLoaded(): void
    {
        if (!$this->deliverySettings) {
            $this->deliverySettings = $this->deliverySettingsProvider->getPayseraDeliverySettings();
        }
    }
}

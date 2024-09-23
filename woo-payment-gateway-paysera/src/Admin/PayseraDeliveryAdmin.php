<?php

declare(strict_types=1);

namespace Paysera\Admin;

defined('ABSPATH') || exit;

use Paysera\Entity\PayseraDeliveryOrderRequest;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\Helper\CallbackHelper;
use Paysera\Helper\EventHandlingHelper;
use Paysera\Helper\LogHelper;
use Paysera\Helper\PayseraDeliveryHelper;
use Paysera\Helper\PayseraDeliveryLibraryHelper;
use Paysera\Helper\PayseraDeliveryOrderRequestHelper;
use Paysera\Helper\SessionHelperInterface;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Service\LoggerInterface;
use WC_Order;

class PayseraDeliveryAdmin
{
    public const TAB_GENERAL_SETTINGS = 'general_settings';
    public const TAB_EXTRA_SETTINGS = 'extra_settings';
    public const TAB_DELIVERY_GATEWAYS_LIST_SETTINGS = 'delivery_gateways_list_settings';

    private PayseraAdminHtml $payseraAdminHtml;
    private PayseraDeliveryAdminHtml $payseraDeliveryAdminHtml;
    private PayseraDeliveryLibraryHelper $payseraDeliveryLibraryHelper;
    private PayseraDeliverySettingsProvider $payseraDeliverySettingsProvider;
    private PayseraDeliveryHelper $payseraDeliveryHelper;
    private LoggerInterface $logger;
    private EventHandlingHelper $eventHandlingHelper;

    private string $tab;
    /**
     * @var string[]
     */
    private array $tabs;

    public function __construct(
        PayseraDeliveryHelper $payseraDeliveryHelper,
        PayseraDeliveryLibraryHelper $payseraDeliveryLibraryHelper,
        LoggerInterface $logger,
        PayseraDeliverySettingsProvider $payseraDeliverySettingsProvider,
        EventHandlingHelper $eventHandlingHelper
    ) {
        $this->payseraAdminHtml = new PayseraAdminHtml();
        $this->payseraDeliveryLibraryHelper = $payseraDeliveryLibraryHelper;
        $this->payseraDeliveryHelper = $payseraDeliveryHelper;
        $this->payseraDeliveryAdminHtml = new PayseraDeliveryAdminHtml($this->payseraDeliveryHelper);
        $this->payseraDeliverySettingsProvider = $payseraDeliverySettingsProvider;
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
        add_action('admin_init', [$this, 'settingsInit']);
        add_action('woocommerce_checkout_order_processed', [$this, 'wcCheckoutOrderProcessed'], 1, 3);
        add_action('woocommerce_store_api_checkout_order_processed', [$this, 'wcStoreApiCheckoutOrderProcessed'], 100, 1);
        add_action('woocommerce_admin_order_data_after_shipping_address', [$this, 'setShippingHouseField'], 10, 1);
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'setBillingHouseField'], 10, 1);
        add_action('woocommerce_product_options_dimensions', [$this, 'appendDimensionsHint']);
    }

    public function settingsInit(): void
    {
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

        register_setting(PayseraDeliverySettings::SETTINGS_NAME, PayseraDeliverySettings::SETTINGS_NAME);
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
        if (isset($_REQUEST['settings-updated'])) {
            printf($this->payseraAdminHtml->getSettingsSavedMessage());
        }

        if (
            (
                empty($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->getProjectId())
                || empty($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->getProjectPassword())
            )
            && isset($_REQUEST['enabled_massage'])
            && sanitize_text_field(wp_unslash($_REQUEST['enabled_massage'])) === 'yes'
        ) {
            printf($this->payseraAdminHtml->getSettingsWarningNotice());
        }

        if (
            !isset($_REQUEST['settings-updated'])
            && isset($_REQUEST['invalid-credentials'])
            && sanitize_text_field(wp_unslash($_REQUEST['invalid-credentials'])) === 'yes'
        ) {
            printf($this->payseraAdminHtml->getSettingsInvalidCredentialsNotice());
        }

        $this->payseraDeliveryAdminHtml->buildDeliverySettings(
            $_GET['tab'] ?? $this->tab,
            $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->getProjectId()
        );
    }

    public function payseraDeliverySettingsSectionCallback(): void
    {
    }

    public function enableRender(): void
    {
        printf(
            $this->payseraDeliveryAdminHtml->enablePayseraDeliveryHtml(
                $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isEnabled()
            )
        );
    }

    public function projectIdRender(): void
    {
        printf(
            $this->payseraAdminHtml->getNumberInput(),
            esc_attr(PayseraDeliverySettings::SETTINGS_NAME . '[' . PayseraDeliverySettings::PROJECT_ID . ']'),
            esc_attr($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->getProjectId()),
            0
        );
    }

    public function projectPasswordRender(): void
    {
        printf(
            $this->payseraAdminHtml->getTextInput(),
            esc_attr(PayseraDeliverySettings::SETTINGS_NAME . '[' . PayseraDeliverySettings::PROJECT_PASSWORD . ']'),
            esc_attr($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->getProjectPassword())
        );
    }

    public function testModeRender(): void
    {
        printf(
            $this->payseraAdminHtml->getEnableInput(
                PayseraDeliverySettings::SETTINGS_NAME . '[' . PayseraDeliverySettings::TEST_MODE . ']',
                $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isTestModeEnabled()
                    ? 'yes' : 'no'
            )
        );
    }

    public function houseNumberFieldRender(): void
    {
        printf(
            $this->payseraAdminHtml->getEnableInput(
                PayseraDeliverySettings::SETTINGS_NAME . '[' . PayseraDeliverySettings::HOUSE_NUMBER_FIELD . ']',
                $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()
                    ->isHouseNumberFieldEnabled() ? 'yes' : 'no'
            )
        );
    }

    public function gridViewRender(): void
    {
        printf(
            $this->payseraAdminHtml->getEnableInput(
                PayseraDeliverySettings::EXTRA_SETTINGS_NAME . '[' . PayseraDeliverySettings::GRID_VIEW . ']',
                $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isGridViewEnabled()
                    ? 'yes' : 'no'
            )
        );
    }

    public function hideShippingMethodsRender(): void
    {
        printf(
            $this->payseraAdminHtml->getEnableInput(
                PayseraDeliverySettings::EXTRA_SETTINGS_NAME . '[' . PayseraDeliverySettings::HIDE_SHIPPING_METHODS . ']',
                $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isHideShippingMethodsEnabled()
                    ? 'yes' : 'no'
            ) .
            $this->payseraAdminHtml->buildLabel(
                __('Hide shipping methods that are above or under set weight limits', PayseraPaths::PAYSERA_TRANSLATIONS)
            )
        );
    }

    public function logLevelOptionsRender(): void
    {
        $logHelper = new LogHelper();

        printf(
            $this->payseraAdminHtml->getLogLevelHtml(
                PayseraDeliverySettings::EXTRA_SETTINGS_NAME . '[' . PayseraDeliverySettings::LOG_LEVEL . ']',
                $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->getLogLevel(),
                LogHelper::LOGGER_TYPE_DELIVERY,
                $logHelper
            )
        );
    }

    public function buildDeliveryGatewaysList(): void
    {
        $deliveryGateways = $this->payseraDeliveryLibraryHelper->getPayseraDeliveryGateways();
        $payseraDeliveryActions = $this->payseraDeliveryLibraryHelper->getPayseraDeliveryActions();
        $payseraDeliveryActions->setDeliveryGatewayTitles($deliveryGateways);
        $payseraDeliveryActions->reSyncDeliveryGatewayStatus($deliveryGateways);
        $payseraDeliveryActions->syncShipmentMethodsStatus(
            $this->payseraDeliveryLibraryHelper->getPayseraShipmentMethods()
        );

        if (empty($deliveryGateways) === false) {
            printf(
                $this->payseraDeliveryAdminHtml->buildDeliveryGatewaysHtml(
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

    public function setShippingHouseField(WC_Order $order): void
    {
        foreach ($order->get_meta_data() as $metaData) {
            if ($metaData->get_data()['key'] === '_shipping_house_no') {
                echo '<div class="address"><p><strong>' . __('House Number', PayseraPaths::PAYSERA_TRANSLATIONS) . ':</strong> '
                    . $metaData->get_data()['value'] . '</p></div>'
                ;
            }
        }
    }

    public function setBillingHouseField(WC_Order $order): void
    {
        foreach ($order->get_meta_data() as $metaData) {
            if ($metaData->get_data()['key'] === '_billing_house_no') {
                echo '<div class="address"><p><strong>' . __('House Number', PayseraPaths::PAYSERA_TRANSLATIONS) . ':</strong> '
                    . $metaData->get_data()['value'] . '</p></div>'
                ;
            }
        }
    }

    public function appendDimensionsHint(): void
    {
        if (!wc_product_dimensions_enabled()) {
            return;
        }

        if (count($this->payseraDeliverySettingsProvider->getActivePayseraDeliveryGateways()) <= 0) {
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
}

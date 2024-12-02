<?php
/*
  Plugin Name: WooCommerce Payment Gateway - Paysera
  Plugin URI: https://www.paysera.com
  Text Domain: paysera
  Domain Path: /languages
  Description: Paysera offers payment and delivery gateway services for your e-shops
  Version: 3.5.10
  Requires PHP: 7.4
  Author: Paysera
  Author URI: https://www.paysera.com
  License: GPL version 3 or later - http://www.gnu.org/licenses/gpl-3.0.html

  WC requires at least: 8.2
  WC tested up to: 9.2

  @package WordPress
  @author Paysera (https://www.paysera.com)
  @since 2.0.0
 */

defined('ABSPATH') || exit;

use Paysera\Action\PayseraDeliveryActions;
use Paysera\Action\PayseraPaymentActions;
use Paysera\Admin\PayseraAdmin;
use Paysera\Admin\PayseraDeliveryAdmin;
use Paysera\Admin\PayseraPaymentAdmin;
use Paysera\Blocks\ShippingIntegrationBlock;
use Paysera\Builder\DatabaseBuilder;
use Paysera\Builder\ShipmentRequestTableBuilder;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\Entity\PayseraPaymentSettings;
use Paysera\EventHandler\DeliveryOrderUpdatedHandler;
use Paysera\EventHandler\WCOrderCreatedHandler;
use Paysera\Factory\LoggerFactory;
use Paysera\Factory\PayseraDeliveryActionsFactory;
use Paysera\Front\PayseraDeliveryFrontHtml;
use Paysera\Helper\CallbackHelper;
use Paysera\Helper\DatabaseHelper;
use Paysera\Helper\EventHandlingHelper;
use Paysera\Helper\LogHelper;
use Paysera\Helper\PayseraDeliveryHelper;
use Paysera\Helper\PayseraDeliveryLibraryHelper;
use Paysera\Helper\PayseraDeliveryOrderHelper;
use Paysera\Helper\PayseraDeliveryOrderRequestHelper;
use Paysera\Helper\SessionHelperInterface;
use Paysera\Helper\WCOrderFieldUpdateHelper;
use Paysera\Helper\WCOrderMetaUpdateHelper;
use Paysera\Helper\WCSessionHelper;
use Paysera\PayseraInit;
use Paysera\Provider\MerchantClientProvider;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Service\LoggerInterface;
use Paysera\Service\PayseraDeliveryOrderService;

class PayseraWoocommerce
{
    const PAYSERA_MIN_REQUIRED_PHP_VERSION = '7.4';
    const PAYSERA_PLUGIN_VERSION = '3.5.10';
    public static $isInitialized = false;
    private DatabaseHelper $databaseHelper;
    private PayseraDeliveryAdmin $payseraDeliveryAdmin;
    private LoggerInterface $deliveryLogger;
    private PayseraDeliveryActions $payseraDeliveryActions;
    private PayseraDeliveryHelper $payseraDeliveryHelper;
    private PayseraDeliverySettingsProvider $payseraDeliverySettingsProvider;
    private SessionHelperInterface $sessionHelper;
    private LoggerFactory $loggerFactory;
    private PayseraDeliveryLibraryHelper $payseraDeliveryLibraryHelper;
    private EventHandlingHelper $eventHandlingHelper;

    public function __construct()
    {
        require __DIR__ . '/vendor/autoload.php';

        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        $this->defineConstants();

        global $wpdb;

        $this->loggerFactory = new LoggerFactory();
        $databaseBuilder = new DatabaseBuilder([
            new ShipmentRequestTableBuilder($wpdb->prefix . 'paysera_delivery_shipping_request'),
        ]);
        $this->databaseHelper = new DatabaseHelper($databaseBuilder);
        $this->deliveryLogger = $this->loggerFactory->createLogger(LogHelper::LOGGER_TYPE_DELIVERY);
        $this->payseraDeliveryActions = (new PayseraDeliveryActionsFactory())->create();
        $this->payseraDeliveryLibraryHelper = new PayseraDeliveryLibraryHelper(
            $this->payseraDeliveryActions,
            new MerchantClientProvider($this->deliveryLogger),
            $this->deliveryLogger
        );
        $this->sessionHelper = new WCSessionHelper();
        $this->payseraDeliveryHelper = new PayseraDeliveryHelper($this->payseraDeliveryLibraryHelper, $this->sessionHelper);
        $this->payseraDeliverySettingsProvider = new PayseraDeliverySettingsProvider();
        $this->eventHandlingHelper = (new EventHandlingHelper());
        $this->payseraDeliveryAdmin = new PayseraDeliveryAdmin(
            $this->payseraDeliveryHelper,
            $this->payseraDeliveryLibraryHelper,
            $this->deliveryLogger,
            $this->payseraDeliverySettingsProvider,
            $this->eventHandlingHelper
        );

        add_action('plugins_loaded', [$this, 'loadPluginInternalDependencies']);
        add_action('woocommerce_loaded', [$this, 'initEventHandlers']);
        add_action('woocommerce_loaded', [$this, 'initPlugin']);
        add_action('woocommerce_blocks_loaded', [$this, 'initBlocks']);
        add_action('admin_init', [$this, 'maybeDeactivatePlugin']);
        add_action('admin_notices', [$this, 'maybeShowWoocommerceMissingNotice']);
        add_action('admin_notices', [$this, 'maybeShowPayseraMinPhpVersionNotice']);
        add_action('admin_post_paysera_log_archive_download', [$this, 'downloadLogArchive']);
    }

    public function activate(): void
    {
        if (self::$isInitialized) {
            return;
        }

        if (
            !$this->isPhpSupported()
            || !$this->hasWoocommerce()
        ) {
            set_transient('paysera_plugins_needs_deactivation', true, 5);
            return;
        }

        $this->payseraDeliveryActions
            ->updateExtraSettingsOption(PayseraDeliverySettings::HIDE_SHIPPING_METHODS, 'yes');

        self::$isInitialized = true;
    }

    public function maybeDeactivatePlugin(): void
    {
        if (get_transient('paysera_plugins_needs_deactivation')) {
            deactivate_plugins(plugin_basename(__FILE__));

            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }

            delete_transient('paysera_plugins_needs_deactivation');
        }
    }

    public function loadPluginInternalDependencies(): void
    {
        $this->loadTextDomain();
        $this->loadDeliverySettings();
        $this->databaseHelper->applySchemaChanges();
    }

    public function loadTextDomain(): void
    {
        load_plugin_textdomain(
            PayseraPaths::PAYSERA_TRANSLATIONS,
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    private function loadDeliverySettings()
    {
        $settings = get_option(PayseraDeliverySettings::SETTINGS_NAME);
        if (!isset($settings[PayseraDeliverySettings::ENABLED])) {
            $deliverySettings = $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings();
            $this->payseraDeliveryActions->updateSettingsOption(
                PayseraDeliverySettings::ENABLED,
                !empty($deliverySettings->getProjectId()) && !empty($deliverySettings->getProjectPassword()) ? 'yes' : 'no'
            );
        }
    }

    public function initPlugin(): void
    {
        $paymentLogger = $this->loggerFactory->createLogger(LogHelper::LOGGER_TYPE_PAYMENT);

        (new PayseraInit(
            $this->payseraDeliveryHelper,
            $this->sessionHelper,
            $this->eventHandlingHelper,
            $paymentLogger,
            $this->deliveryLogger,
        ))->build();
        (new PayseraAdmin($this->payseraDeliveryAdmin))->build();
        $this->payseraDeliveryAdmin->build();
        (new PayseraPaymentAdmin())->build();
        $this->payseraDeliveryActions->build();
        (new PayseraDeliveryFrontHtml(
            $this->payseraDeliveryHelper,
            $this->payseraDeliverySettingsProvider,
            $this->sessionHelper
        ))->build();
        (new PayseraPaymentActions($paymentLogger))->build();
    }

    public function initBlocks(): void
    {
        add_action('woocommerce_blocks_checkout_block_registration', [$this, 'registerCheckoutBlocks']);
    }

    public function registerCheckoutBlocks($integrationRegistry): void
    {
        $integrationRegistry->register(
            new ShippingIntegrationBlock(
                $this->payseraDeliveryHelper,
                $this->payseraDeliverySettingsProvider,
                $this->sessionHelper
            )
        );
    }

    public function isPhpSupported(): bool
    {
        return version_compare(PHP_VERSION, self::PAYSERA_MIN_REQUIRED_PHP_VERSION, '>=')
            ? true : false;
    }

    public function hasWoocommerce(): bool
    {
        return class_exists('WooCommerce');
    }

    public function isWoocommerceInstalled(): bool
    {
        return in_array(
            'woocommerce/woocommerce.php',
            array_keys(get_plugins()),
            true
        );
    }

    public function deactivate(): void
    {
        delete_option(PayseraPaymentSettings::MAIN_SETTINGS_NAME);
        delete_option(PayseraPaymentSettings::EXTRA_SETTINGS_NAME);
        delete_option(PayseraPaymentSettings::STATUS_SETTINGS_NAME);
        delete_option(PayseraPaymentSettings::PROJECT_ADDITIONS_SETTINGS_NAME);
        delete_option(PayseraDeliverySettings::SETTINGS_NAME);
        delete_option(PayseraDeliverySettings::DELIVERY_GATEWAYS_SETTINGS_NAME);
        delete_option(PayseraDeliverySettings::DELIVERY_GATEWAYS_TITLES);
        delete_option(PayseraPaths::WOOCOMMERCE_PAYSERA_SETTINGS);

        if (class_exists('WC_Cache_Helper')) {
            WC_Cache_Helper::get_transient_version('shipping', true);
        }

        $this->databaseHelper->revertSchemaChanges();
    }

    public function defineConstants(): void
    {
        if (!defined('PAYSERA_PLUGIN_VERSION')) {
            define('PAYSERA_PLUGIN_VERSION', self::PAYSERA_PLUGIN_VERSION);
        }
        if (!defined('PAYSERA_MIN_REQUIRED_PHP_VERSION')) {
            define('PAYSERA_MIN_REQUIRED_PHP_VERSION', self::PAYSERA_MIN_REQUIRED_PHP_VERSION);
        }
        if (!defined('PayseraPluginUrl')) {
            define('PayseraPluginUrl', plugin_dir_url(__FILE__));
        }
        if (!defined('PayseraPluginPath')) {
            define('PayseraPluginPath', untrailingslashit(plugin_dir_path(__FILE__)));
        }
    }

    public function maybeShowWoocommerceMissingNotice(): void
    {
        if ($this->hasWoocommerce()) {
            return;
        }

        $this->showWoocommerceMissingNotice();
    }

    public function maybeShowPayseraMinPhpVersionNotice(): void
    {
        if ($this->isPhpSupported()) {
            return;
        }

        $this->showPayseraMinPhpVersionNotice();
    }

    public function showWoocommerceMissingNoticeOnActivation(): void
    {
    ?>
        <div class="error">
            <p><b><?php esc_html_e('Paysera Payment And Delivery', PayseraPaths::PAYSERA_TRANSLATIONS); ?></b></p>
            <p><?php esc_html_e($this->getDepencyErrorMessages()['woocommerce_missing']); ?></p>
            <p>
                <a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="button button-primary">
                    « <?php esc_html_e('Go back', PayseraPaths::PAYSERA_TRANSLATIONS); ?>
                </a>
            </p>
        </div>
    <?php
    }

    public function showWoocommerceMissingNotice(): void
    {
    ?>
        <div class="error">
            <p><b><?php esc_html_e(__('Paysera Payment And Delivery', PayseraPaths::PAYSERA_TRANSLATIONS)); ?></b></p>
            <p><?php esc_html_e($this->getDepencyErrorMessages()['woocommerce_missing']); ?></p>
        </div>
    <?php
    }

    public function showPayseraMinPhpVersionNotice(): void
    {
    ?>
        <div class="error">
            <p><b><?php esc_html_e(__('Paysera Payment And Delivery', PayseraPaths::PAYSERA_TRANSLATIONS)); ?></b></p>
            <p><?php esc_html_e($this->getDepencyErrorMessages()['php_min_version']); ?></p>
        </div>
    <?php
    }

    public function getDepencyErrorMessages(): array
    {
        return [
            'woocommerce_missing' => __(
                'The Paysera plugin requires WooCommerce to be installed and activated.',
                PayseraPaths::PAYSERA_TRANSLATIONS
            ),
            'php_min_version' => sprintf(
                /* translators: 1: Min Required PHP Version */
                __('Paysera plugin requires at least PHP %s', PayseraPaths::PAYSERA_TRANSLATIONS),
                self::PAYSERA_MIN_REQUIRED_PHP_VERSION
            ),
        ];
    }

    public function downloadLogArchive(): void
    {
        $logHelper = new LogHelper();
        $zipFileName = $logHelper->generateZipArchive($_GET['logger_type']);

        if ($zipFileName === null) {
            wp_die(__('Failed to create a log archive', PayseraPaths::PAYSERA_TRANSLATIONS));
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($zipFileName) . '"');
        header('Content-Length: ' . filesize($zipFileName));
        readfile($zipFileName);
        unlink($zipFileName);

        exit;
    }

    public function initEventHandlers(): void
    {
        $this->eventHandlingHelper
            ->registerHandler(
                PayseraDeliverySettings::DELIVERY_ORDER_EVENT_UPDATED,
                new DeliveryOrderUpdatedHandler(
                    $this->payseraDeliveryHelper,
                    $this->payseraDeliveryLibraryHelper,
                    $this->deliveryLogger,
                    new WCOrderMetaUpdateHelper(),
                    new WCOrderFieldUpdateHelper(),
                )
            )
            ->registerHandler(
                PayseraDeliverySettings::WC_ORDER_EVENT_CREATED,
                new WCOrderCreatedHandler(
                    new PayseraDeliveryOrderService(
                        new PayseraDeliveryOrderRequestHelper(
                            new PayseraDeliveryOrderHelper(
                                $this->payseraDeliverySettingsProvider,
                                $this->payseraDeliveryLibraryHelper,
                                $this->payseraDeliveryHelper,
                                $this->deliveryLogger,
                                $this->sessionHelper,
                                new CallbackHelper(),
                            ),
                            $this->deliveryLogger,
                        ),
                        $this->deliveryLogger,
                    ),
                    $this->payseraDeliveryLibraryHelper,
                    $this->payseraDeliverySettingsProvider,
                    $this->payseraDeliveryHelper,
                    $this->sessionHelper,
                    $this->deliveryLogger,
                )
            );
    }
}

/**
 * Load Paysera Plugin when all plugins loaded.
 *
 * @return PayseraWoocommerce
 */
function payseraWoocommerce(): PayseraWoocommerce
{
    return new PayseraWoocommerce();
}

payseraWoocommerce();

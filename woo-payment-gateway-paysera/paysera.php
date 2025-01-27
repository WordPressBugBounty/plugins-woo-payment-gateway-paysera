<?php
/*
  Plugin Name: WooCommerce Payment Gateway - Paysera
  Plugin URI: https://www.paysera.com
  Text Domain: paysera
  Domain Path: /languages
  Description: Paysera offers payment and delivery gateway services for your e-shops
  Version: 3.6.0.3
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
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\Entity\PayseraPaymentSettings;
use Paysera\Front\PayseraDeliveryFrontHtml;
use Paysera\Helper\DatabaseHelper;
use Paysera\Helper\LogHelper;
use Paysera\Helper\PayseraDeliveryHelper;
use Paysera\Helper\SessionHelperInterface;
use Paysera\PayseraInit;
use Paysera\Provider\ContainerProvider;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Scoped\Psr\Container\ContainerInterface;
use Paysera\Scoped\Symfony\Component\Dotenv\Dotenv;

class PayseraWoocommerce
{
    const PAYSERA_MIN_REQUIRED_PHP_VERSION = '7.4';
    const PAYSERA_PLUGIN_VERSION = '3.6.0.3';
    public static bool $isInitialized = false;
    private PayseraDeliveryActions $payseraDeliveryActions;
    private ContainerInterface $container;

    public function __construct()
    {
        require __DIR__ . '/vendor/autoload.php';

        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        $this->defineConstants();
        $this->loadEnvVars();

        $this->container = (new ContainerProvider())->getContainer();
        $this->payseraDeliveryActions = $this->container->get(PayseraDeliveryActions::class);

        add_action('plugins_loaded', [$this, 'loadPluginInternalDependencies']);
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
        $this->container->get(DatabaseHelper::class)->applySchemaChanges();
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
            $deliverySettings = $this->container
                ->get(PayseraDeliverySettingsProvider::class)
                ->getPayseraDeliverySettings()
            ;
            $this->payseraDeliveryActions->updateSettingsOption(
                PayseraDeliverySettings::ENABLED,
                !empty($deliverySettings->getProjectId()) && !empty($deliverySettings->getProjectPassword()) ? 'yes' : 'no'
            );
        }
    }

    public function initPlugin(): void
    {
        $this->container->get(PayseraInit::class)->build();
        $this->container->get(PayseraAdmin::class)->build();
        $this->container->get(PayseraDeliveryAdmin::class)->build();
        $this->container->get(PayseraPaymentAdmin::class)->build();
        $this->container->get(PayseraDeliveryActions::class)->build();
        $this->container->get(PayseraDeliveryFrontHtml::class)->build();
        $this->container->get(PayseraPaymentActions::class)->build();
    }

    public function initBlocks(): void
    {
        add_action('woocommerce_blocks_checkout_block_registration', [$this, 'registerCheckoutBlocks']);
    }

    public function registerCheckoutBlocks($integrationRegistry): void
    {
        $integrationRegistry->register(
            new ShippingIntegrationBlock(
                $this->container->get(PayseraDeliveryHelper::class),
                $this->container->get(PayseraDeliverySettingsProvider::class),
                $this->container->get(SessionHelperInterface::class),
            ),
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

        $this->container->get(DatabaseHelper::class)->revertSchemaChanges();
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
        if (!defined('PayseraPluginBuildDir')) {
            define('PayseraPluginBuildDir', __DIR__ . '/');
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

    private function loadEnvVars(): void
    {
        $envFilePath = __DIR__ . '/.env';

        if (file_exists($envFilePath)) {
            $dotenv = new Dotenv();
            $dotenv->usePutenv();
            $dotenv->load($envFilePath);
        }
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

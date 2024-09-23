<?php

declare(strict_types=1);

namespace Paysera;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Paysera\Admin\PayseraDeliveryAdminHtml;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\Entity\PayseraPaymentSettings;
use Paysera\Helper\EventHandlingHelper;
use Paysera\Helper\PayseraDeliveryHelper;
use Paysera\Helper\SessionHelperInterface;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Provider\PayseraPaymentSettingsProvider;
use Paysera\Rest\PayseraDeliveryController;
use Paysera\Rest\PayseraPaymentController;
use Paysera\Service\LoggerInterface;
use WC_Shipping_Rate;

class PayseraInit
{
    private const DELIVERY_CLASS_FILE_TEMPLATE = '%s/Entity/class-paysera-%s-%s-delivery.php';
    private const DELIVERY_GATEWAY_KEY_TEMPLATE = 'paysera_delivery_%s_%s';
    private const DELIVERY_GATEWAY_CLASS_TEMPLATE = 'Paysera_%s_%s_Delivery';

    private PayseraPaymentSettings $payseraPaymentSettings;
    private PayseraDeliveryHelper $payseraDeliveryHelper;
    private PayseraDeliverySettingsProvider $payseraDeliverySettingsProvider;
    private PayseraDeliveryAdminHtml $payseraDeliveryAdminHtml;
    private SessionHelperInterface $sessionHelper;
    private LoggerInterface $paymentLogger;
    private LoggerInterface $deliveryLogger;
    private EventHandlingHelper $eventHandlingHelper;
    private array $notices;
    private array $errors;
    private static ?bool $isAvailableToEnqueueScripts = null;

    public function __construct(
        PayseraDeliveryHelper $payseraDeliveryHelper,
        SessionHelperInterface $sessionHelper,
        EventHandlingHelper $eventHandlingHelper,
        LoggerInterface $paymentLogger,
        LoggerInterface $deliveryLogger
    ) {
        $this->payseraPaymentSettings = (new PayseraPaymentSettingsProvider())->getPayseraPaymentSettings();
        $this->payseraDeliveryHelper = $payseraDeliveryHelper;
        $this->payseraDeliverySettingsProvider = new PayseraDeliverySettingsProvider();
        $this->payseraDeliveryAdminHtml = new PayseraDeliveryAdminHtml($payseraDeliveryHelper);
        $this->sessionHelper = $sessionHelper;
        $this->paymentLogger = $paymentLogger;
        $this->deliveryLogger = $deliveryLogger;
        $this->eventHandlingHelper = $eventHandlingHelper;
        $this->notices = [];
        $this->errors = [];
    }

    public function build()
    {
        $this->requireDeliveryEntities();
        add_action('plugins_loaded', [$this, 'loadPayseraPlugin']);
        add_action('admin_notices', [$this, 'displayAdminErrors']);
        add_action('admin_notices', [$this, 'displayAdminNotices']);
        add_filter('woocommerce_payment_gateways', [$this, 'registerPaymentGateway']);
        add_filter('plugin_action_links_' . PayseraPluginPath . '/paysera.php', [$this, 'addPayseraPluginActionLinks']);
        add_action('wp_head', [$this, 'addMetaTags']);
        add_action('wp_head', [$this, 'addQualitySign']);
        add_filter('woocommerce_shipping_methods', [$this, 'registerDeliveryGateways']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_filter('woocommerce_cart_shipping_method_full_label', [$this, 'deliveryGatewayLogos'], PHP_INT_MAX, 2);
        add_action('admin_notices', [$this, 'payseraDeliveryPluginNotice']);
        add_action('admin_init', [$this, 'payseraDeliveryPluginNoticeDismiss']);
        add_action('woocommerce_init', [$this, 'enableCartFrontendForRestApi']);
        add_action('before_woocommerce_init', [$this, 'declareWooCommerceHighPerformanceOrderStorageCompatibility']);
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        add_action('woocommerce_blocks_enqueue_cart_block_scripts_after', [$this, 'cartScripts']);
    }

    public function loadPayseraPlugin(): bool
    {
        $activePayseraPlugins = $this->getActivePayseraPlugins();

        if (empty($activePayseraPlugins) === false) {
            if (count($activePayseraPlugins) > 1) {
                $this->addNotice(__('More than 1 Paysera plugin active', PayseraPaths::PAYSERA_TRANSLATIONS));
            }
        }

        $this->initDeliveryGateways();

        return true;
    }

    public function payseraDeliveryPluginNotice(): void
    {
        wp_enqueue_style('paysera-payment-css', PayseraPaths::PAYSERA_PAYMENT_CSS);

        $notice = sprintf(
        // translators: 1 - plugin settings link
            __(
                'NEW! With the latest version, you can integrate delivery options into your e-shop. More about the %s ',
                PayseraPaths::PAYSERA_TRANSLATIONS
            ),
            '<a href="' . admin_url(PayseraPaths::PAYSERA_ADMIN_SETTINGS_LINK) . ' "> '
            . __('Plugin & Services.', PayseraPaths::PAYSERA_TRANSLATIONS) . '</a>'
        );

        if (!get_user_meta(wp_get_current_user()->ID, 'paysera_new_delivery_notice')) {
            echo wp_kses(
                '<div class="notice notice-info"><p><b>'
                . __('Paysera Payment And Delivery', PayseraPaths::PAYSERA_TRANSLATIONS) . ': </b>' . $notice
                . '<a href="?paysera-new-delivery-notice-dismiss" class="notice-dismiss paysera-notice-dismiss"></a></p></div>',
                [
                    'div' => ['class' => []],
                    'p' => [],
                    'b' => [],
                    'br' => [],
                    'a' => ['href' => [], 'class' => []],
                ]
            );
        }
    }

    public function payseraDeliveryPluginNoticeDismiss(): void
    {
        if (isset($_GET['paysera-new-delivery-notice-dismiss'])) {
            add_user_meta(wp_get_current_user()->ID, 'paysera_new_delivery_notice', 'true', true);

            wp_safe_redirect('admin.php?page=paysera');
            exit();
        }
    }

    public function displayAdminErrors(): void
    {
        if (empty($this->errors) === false) {
            foreach ($this->errors as $error) {
                echo wp_kses(
                    '<div class="error"><p><b>'
                    . __('Paysera Payment And Delivery', PayseraPaths::PAYSERA_TRANSLATIONS) . ': </b><br>'
                    . $error . '</p></div>',
                    ['div' => ['class' => []], 'p' => [], 'b' => [], 'br' => [], 'a' => ['href' => []]]
                );
            }
        }
    }

    public function displayAdminNotices(): void
    {
        if (empty($this->notices) === false) {
            foreach ($this->notices as $notice) {
                echo wp_kses(
                    '<div class="notice notice-info is-dismissible"><p><b>'
                    . __('Paysera Payment And Delivery', PayseraPaths::PAYSERA_TRANSLATIONS) . ': </b><br>'
                    . $notice . '</p></div>',
                    ['div' => ['class' => []], 'p' => [], 'b' => [], 'br' => [], 'a' => ['href' => []]]
                );
            }
        }
    }

    public function registerPaymentGateway(array $methods): array
    {
        require_once 'Entity/class-paysera-payment-gateway.php';

        $methods[] = 'Paysera_Payment_Gateway';

        return $methods;
    }

    public function addPayseraPluginActionLinks(array $links): array
    {
        wp_enqueue_style('paysera-payment-css', PayseraPaths::PAYSERA_PAYMENT_CSS);

        $documentationLink = '<a href="' . PayseraPaths::PAYSERA_DOCUMENTATION_LINK . '" target="_blank">'
            . __('Documentation', PayseraPaths::PAYSERA_TRANSLATIONS) . '</a>';

        $settingsLink = '<a href="' . admin_url(PayseraPaths::PAYSERA_ADMIN_SETTINGS_LINK) . '">'
            . __('Settings', PayseraPaths::PAYSERA_TRANSLATIONS) . '</a>';

        array_unshift($links, $settingsLink, $documentationLink);

        return $links;
    }

    public function addMetaTags(): void
    {
        if (
            $this->payseraPaymentSettings->isOwnershipCodeEnabled() === true
            && (
                $this->payseraPaymentSettings->getOwnershipCode() !== null
                && $this->payseraPaymentSettings->getOwnershipCode() !== ''
            )
        ) {
            echo wp_kses(
                '<meta name="verify-paysera" content="' . $this->payseraPaymentSettings->getOwnershipCode() . '">',
                ['meta' => ['name' => [], 'content' => []]]
            );
        }
    }

    public function addQualitySign(): void
    {
        if (
            $this->payseraPaymentSettings->isQualitySignEnabled()
            && $this->payseraPaymentSettings->getProjectId() !== null
            && $this->payseraPaymentSettings->isEnabled()
            && self::isAvailableToEnqueueScripts()
        ) {
            $this->addQualitySignScript($this->payseraPaymentSettings->getProjectId());
        }
    }


    public function getDeliveryGateways(): array
    {
        $gateways = [];

        $payseraDeliverySettings = $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings();

        foreach ($payseraDeliverySettings->getDeliveryGateways() as $deliveryGateway => $isEnabled) {
            if ($isEnabled === false) {
                continue;
            }

            foreach (PayseraDeliverySettings::DELIVERY_GATEWAY_TYPE_MAP as $deliveryGatewayType) {
                if (
                    $this->isDeliveryGatewayShippingMethodAllowed(
                        $payseraDeliverySettings->getShipmentMethods(),
                        $deliveryGateway,
                        $deliveryGatewayType
                    )
                ) {
                    $key = sprintf(self::DELIVERY_GATEWAY_KEY_TEMPLATE, $deliveryGateway, $deliveryGatewayType);
                    $gateways[$key] = [$deliveryGateway, $deliveryGatewayType];
                }
            }
        }

        return $gateways;
    }

    public function initDeliveryGateways(): void
    {
        foreach ($this->getDeliveryGateways() as [$deliveryGateway, $deliveryGatewayType]) {
            require_once sprintf(
                self::DELIVERY_CLASS_FILE_TEMPLATE,
                __DIR__,
                $deliveryGateway,
                $deliveryGatewayType
            );
        }
    }

    public function registerDeliveryGateways(array $methods): array
    {
        foreach ($this->getDeliveryGateways() as $key => [$deliveryGateway, $deliveryGatewayType]) {
            $methods[$key] = sprintf(
                self::DELIVERY_GATEWAY_CLASS_TEMPLATE,
                ucfirst($deliveryGateway),
                ucfirst($deliveryGatewayType)
            );
        }

        return $methods;
    }

    private function requireDeliveryEntities()
    {
        $payseraDeliverySettings = $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings();
        foreach ($payseraDeliverySettings->getDeliveryGateways() as $deliveryGateway => $isEnabled) {
            foreach (PayseraDeliverySettings::DELIVERY_GATEWAY_TYPE_MAP as $deliveryGatewayType) {

                if (!$this->payseraDeliveryHelper->deliveryGatewayClassExists($deliveryGateway, $deliveryGatewayType)) {
                    $this->createDeliveryEntity($deliveryGateway, $deliveryGatewayType);
                }

                require_once 'Entity/class-paysera-' . $deliveryGateway . '-' . $deliveryGatewayType . '-delivery.php';
            }
        }
    }

    public function enqueueScripts(): void
    {
        if (PayseraDeliveryHelper::isAvailableForDeliveryToEnqueueScripts()) {
            wp_enqueue_style('paysera-select-2-css', PayseraPaths::PAYSERA_SELECT_2_CSS);
            wp_enqueue_script('paysera-select-2-js', PayseraPaths::PAYSERA_SELECT_2_JS, ['jquery']);
            wp_enqueue_script('paysera-delivery-frontend-js', PayseraPaths::PAYSERA_DELIVERY_FRONTEND_JS, ['jquery']);
            wp_register_script(
                'paysera-delivery-frontend-ajax-js',
                PayseraPaths::PAYSERA_DELIVERY_FRONTEND_AJAX_JS,
                [],
                false,
                ['in_footer' => true]
            );
            wp_enqueue_script('paysera-delivery-frontend-ajax-js');
            wp_localize_script(
                'paysera-delivery-frontend-ajax-js',
                'ajax_object',
                ['ajaxurl' => admin_url('admin-ajax.php')]
            );

            wp_enqueue_style('paysera-shipping-block-frontend-css', PayseraPluginUrl . 'assets/build/style-paysera-shipping-block-frontend.css');

            if ($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isGridViewEnabled() === true) {
                wp_enqueue_style('paysera-delivery-grid-css', PayseraPaths::PAYSERA_DELIVERY_GRID_CSS);
                wp_enqueue_script(
                    'paysera-delivery-frontend-grid-js',
                    PayseraPaths::PAYSERA_DELIVERY_FRONTEND_GRID_JS,
                    ['jquery']
                );
            }
        }
    }

    public function deliveryGatewayLogos(string $label, WC_Shipping_Rate $shippingRate): string
    {
        if (PayseraDeliveryHelper::isAvailableForDeliveryToEnqueueScripts()) {
            wp_enqueue_style('paysera-delivery-css', PayseraPaths::PAYSERA_DELIVERY_CSS);
        }

        if (
            empty($this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->getDeliveryGateways())
            === true
            || $this->payseraDeliveryHelper->isPayseraDeliveryGateway($shippingRate->get_method_id()) === false
        ) {
            return $label;
        }

        $totalWeight = 0;

        foreach (WC()->cart->cart_contents as $item) {
            $product = wc_get_product($item['product_id']);

            $totalWeight += (float)($product->get_weight() ?? 0) * (float)$item['quantity'];
        }

        if (get_option('woocommerce_weight_unit') === 'g') {
            $totalWeight /= 1000;
        }

        $payseraDeliveryGatewaySettings = $this->payseraDeliverySettingsProvider->getPayseraDeliveryGatewaySettings(
            $shippingRate->get_method_id(),
            $shippingRate->get_instance_id()
        );

        $maximumWeight = $payseraDeliveryGatewaySettings->getMaximumWeight();
        $minimumWeight = $payseraDeliveryGatewaySettings->getMinimumWeight();
        $error = null;

        if ($totalWeight > $maximumWeight || $totalWeight < $minimumWeight) {
            $error = __('Cart weight is not sufficient', PayseraPaths::PAYSERA_TRANSLATIONS);

            if ($totalWeight > $maximumWeight) {
                $error = __('Cart weight is exceeded', PayseraPaths::PAYSERA_TRANSLATIONS);
            }

            $label .= '<p class="paysera-delivery-error">' . $error . '</p>';
        }

        foreach ($this->payseraDeliveryHelper->getPayseraDeliveryGateways() as $deliveryGateway) {
            if (
                $shippingRate->get_method_id() === PayseraDeliverySettings::DELIVERY_GATEWAY_PREFIX
                . $deliveryGateway->getCode() . '_courier'
                || $shippingRate->get_method_id() === PayseraDeliverySettings::DELIVERY_GATEWAY_PREFIX
                . $deliveryGateway->getCode() . '_terminals'
            ) {
                if ($error === null) {
                    $label .= '<br>';
                }

                $label .= $this->payseraDeliveryAdminHtml->generateDeliveryGatewayLogoHtml($deliveryGateway, true);
            }
        }

        return $label;
    }

    public function declareWooCommerceHighPerformanceOrderStorageCompatibility(): void
    {
        if (class_exists(FeaturesUtil::class)) {
            FeaturesUtil::declare_compatibility('custom_order_tables', PayseraPluginPath . '/paysera.php');
        }
    }

    private function getActivePayseraPlugins(): array
    {
        $plugins = [];

        foreach (get_option('active_plugins') as $activePlugin) {
            if (strpos($activePlugin, 'paysera') !== false) {
                $plugins[] = $activePlugin;
            }
        }

        return $plugins;
    }

    private function addError(string $errorText): void
    {
        $this->errors[] = __($errorText, PayseraPaths::PAYSERA_TRANSLATIONS);
    }

    private function addNotice(string $noticeText): void
    {
        $this->notices[] = __($noticeText, PayseraPaths::PAYSERA_TRANSLATIONS);
    }

    private function addQualitySignScript(int $projectId): void
    {
        wp_enqueue_script(
            'paysera-payment-quality-sign-js',
            PayseraPaths::PAYSERA_PAYMENT_QUALITY_SIGN_JS,
            ['jquery']
        );
        wp_localize_script(
            'paysera-payment-quality-sign-js',
            'data',
            [
                'project_id' => $projectId,
                'language' => explode('_', get_locale())[0],
            ]
        );
    }

    private function isDeliveryGatewayShippingMethodAllowed(
        array $shipmentMethods,
        string $deliveryGateway,
        string $deliveryGatewayType
    ): bool {
        if (
            $deliveryGatewayType === PayseraDeliverySettings::TYPE_TERMINALS
            && in_array($deliveryGateway, PayseraDeliverySettings::PARCEL_MACHINE_DISABLED_DELIVERY_GATEWAYS, true)
        ) {
            return false;
        }

        if (
            (
                (
                    $shipmentMethods[PayseraDeliverySettings::SHIPMENT_METHOD_COURIER_2_COURIER] === true
                    || $shipmentMethods[PayseraDeliverySettings::SHIPMENT_METHOD_PARCEL_MACHINE_2_COURIER] === true
                )
                && $deliveryGatewayType === PayseraDeliverySettings::TYPE_COURIER
            )
            || (
                (
                    $shipmentMethods[PayseraDeliverySettings::SHIPMENT_METHOD_COURIER_2_PARCEL_MACHINE] === true
                    || $shipmentMethods[PayseraDeliverySettings::SHIPMENT_METHOD_PARCEL_MACHINE_2_PARCEL_MACHINE] === true
                )
                && $deliveryGatewayType === PayseraDeliverySettings::TYPE_TERMINALS
            )
        ) {
            return true;
        }

        return false;
    }

    private function createDeliveryEntity(string $deliveryGateway, string $deliveryGatewayType): void
    {
        $deliveryEntity = 'Paysera_' . ucfirst($deliveryGateway) . '_' . ucfirst($deliveryGatewayType) . '_Delivery';

        $description = '%s courier will deliver the parcel to the selected parcel terminal for customer to pickup any time.';

        if ($deliveryGatewayType === PayseraDeliverySettings::TYPE_COURIER) {
            $description = "%s courier will deliver the parcel right to the customer\'s hands.";
        }

        $receiverType = $deliveryGatewayType === PayseraDeliverySettings::TYPE_COURIER ?
            PayseraDeliverySettings::TYPE_COURIER : PayseraDeliverySettings::TYPE_PARCEL_MACHINE;

        $deliveryGatewayTitles = $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()
            ->getDeliveryGatewayTitles();
        $deliveryGatewayTitle = $deliveryGatewayTitles[$deliveryGateway] . ' '
            . __(ucfirst($deliveryGatewayType), PayseraPaths::PAYSERA_TRANSLATIONS);

        $entityContent = '<?php

declare(strict_types=1);

defined(\'ABSPATH\') || exit;

if (class_exists(\'Paysera_Delivery_Gateway\') === false) {
    require_once \'abstract-paysera-delivery-gateway.php\';
}

class ' . $deliveryEntity . ' extends Paysera_Delivery_Gateway
{
    public $deliveryGatewayCode = \'' . $deliveryGateway . '_' . $deliveryGatewayType . '\';
    public $defaultTitle = \'' . $deliveryGatewayTitle . '\';
    public $receiverType = \'' . $receiverType . '\';
    public $defaultDescription = \'' . $description . '\';
}
';

        file_put_contents(
            plugin_dir_path(__FILE__) . 'Entity/class-paysera-' . $deliveryGateway . '-'
            . $deliveryGatewayType . '-delivery.php',
            $entityContent
        );
    }

    public function registerRestRoutes(): void
    {
        $restApiRoutes = [
            new PayseraDeliveryController(
                $this->payseraDeliveryHelper,
                $this->sessionHelper,
                $this->deliveryLogger,
                $this->eventHandlingHelper
            ),
            new PayseraPaymentController($this->paymentLogger),
        ];

        foreach ($restApiRoutes as $restApiRoute) {
            $restApiRoute->registerRoutes();
        }
    }

    public function enableCartFrontendForRestApi(): void
    {
        if (!WC()->is_rest_api_request()) {
            return;
        }

        WC()->frontend_includes();

        if (null === WC()->cart && function_exists('wc_load_cart')) {
            wc_load_cart();
        }
    }

    public function cartScripts()
    {
        if (PayseraDeliveryHelper::isAvailableForDeliveryToEnqueueScripts()) {
            wp_enqueue_script('paysera-cart-logos-js', PayseraPaths::PAYSERA_DELIVERY_CART_LOGOS_JS);

            wp_localize_script(
                'paysera-cart-logos-js',
                'data',
                [
                    'shippingLogos' => $this->payseraDeliveryHelper->getShippingOptionLogoUrls(),
                ]
            );
        }
    }

    public static function isAvailableToEnqueueScripts(): bool
    {
        if (self::$isAvailableToEnqueueScripts === null) {
            $pageId = url_to_postid(set_url_scheme('//' .$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']));
            self::$isAvailableToEnqueueScripts = $pageId === wc_get_page_id( 'checkout' ) || $pageId === wc_get_page_id( 'cart' );
        }

        return self::$isAvailableToEnqueueScripts;
    }
}

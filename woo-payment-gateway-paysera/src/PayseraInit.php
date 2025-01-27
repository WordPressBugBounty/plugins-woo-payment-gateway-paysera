<?php

declare(strict_types=1);

namespace Paysera;

defined('ABSPATH') || exit;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Evp\Component\Money\Money;
use Paysera\Admin\PayseraDeliveryAdminHtml;
use Paysera\Scoped\Paysera\DeliverySdk\Service\DeliveryLoggerInterface;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\Entity\PayseraPaymentSettings;
use Paysera\Helper\EventHandlingHelper;
use Paysera\Helper\PayseraDeliveryHelper;
use Paysera\Helper\PayseraHTMLHelper;
use Paysera\Helper\SessionHelperInterface;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Provider\PayseraPaymentSettingsProvider;
use Paysera\Rest\PayseraDeliveryController;
use Paysera\Rest\PayseraPaymentController;
use Paysera\Service\LoggerInterface;
use Paysera\Service\PaymentLoggerInterface;
use Throwable;
use Paysera\Validation\PayseraDeliveryWeightValidator;
use WC_Shipping_Rate;

class PayseraInit
{
    public const DELIVERY_GATEWAY_WEIGHT_HINT_SESSION_KEY = 'paysera_cart_weight_restrictions_hints';
    private const DELIVERY_CLASS_FILE_TEMPLATE = '%s/Entity/class-paysera-%s-%s-delivery.php';
    private const DELIVERY_GATEWAY_KEY_TEMPLATE = 'paysera_delivery_%s_%s';
    private const DELIVERY_GATEWAY_CLASS_TEMPLATE = 'Paysera_%s_%s_Delivery';
    private const SIGN_ASSETS_INIT_ACTION_KEY = 'paysera_enqueue_sign_assets';
    private const DELIVERY_GATEWAY_WEIGHT_HINT_ACTION_KEY = 'paysera_cart_weight_restrictions_hints';

    private PayseraPaymentSettings $paymentSettings;
    private PayseraDeliveryHelper $deliveryHelper;
    private PayseraDeliverySettingsProvider $deliverySettingsProvider;
    private PayseraDeliveryAdminHtml $deliveryAdminHtml;
    private SessionHelperInterface $sessionHelper;
    private LoggerInterface $paymentLogger;
    private DeliveryLoggerInterface $deliveryLogger;
    private EventHandlingHelper $eventHandlingHelper;
    private array $notices;
    private array $errors;

    public function __construct(
        PayseraPaymentSettingsProvider $paymentSettingsProvider,
        PayseraDeliverySettingsProvider $deliverySettingsProvider,
        PayseraDeliveryHelper $deliveryHelper,
        SessionHelperInterface $sessionHelper,
        EventHandlingHelper $eventHandlingHelper,
        PayseraDeliveryAdminHtml $deliveryAdminHtml,
        PaymentLoggerInterface $paymentLogger,
        DeliveryLoggerInterface $deliveryLogger
    ) {
        $this->paymentSettings = $paymentSettingsProvider->getPayseraPaymentSettings();
        $this->deliveryHelper = $deliveryHelper;
        $this->deliverySettingsProvider = $deliverySettingsProvider;
        $this->deliveryAdminHtml = $deliveryAdminHtml;
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
        add_action(self::SIGN_ASSETS_INIT_ACTION_KEY, [$this, 'qualitySignAssets']);
        add_action('woocommerce_blocks_enqueue_cart_block_scripts_after', [$this, 'initQualitySign']);
        add_action('woocommerce_blocks_enqueue_checkout_block_scripts_after', [$this, 'initQualitySign']);
        add_action('woocommerce_after_cart', [$this, 'initQualitySign']);
        add_action('woocommerce_after_checkout_form', [$this, 'initQualitySign']);
        add_filter('woocommerce_shipping_methods', [$this, 'registerDeliveryGateways']);
        add_action(self::DELIVERY_GATEWAY_WEIGHT_HINT_ACTION_KEY, [$this, 'initDeliveryGatewayWeightRestrictionsHint']);
        if (version_compare(get_bloginfo('version'), '6.5', '<')) {
            add_filter('woocommerce_cart_shipping_packages', [$this, 'applyDeliveryGatewayWeightRestrictionsHint'], PHP_INT_MAX, 2);
        } else {
            add_filter('woocommerce_package_rates', [$this, 'applyDeliveryGatewayWeightRestrictionsHint'], PHP_INT_MAX, 2);
        }
        add_filter('woocommerce_cart_shipping_method_full_label', [$this, 'deliveryGatewayLogos'], PHP_INT_MAX, 2);
        add_action('admin_notices', [$this, 'payseraDeliveryPluginNotice']);
        add_action('admin_init', [$this, 'payseraDeliveryPluginNoticeDismiss']);
        add_action('woocommerce_init', [$this, 'enableCartFrontendForRestApi']);
        add_action('before_woocommerce_init', [$this, 'declareWooCommerceHighPerformanceOrderStorageCompatibility']);
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        add_filter('woocommerce_order_received_verify_known_shoppers', [$this, 'restrictOrderReceivedFromUnknownClient'] );
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
        PayseraHTMLHelper::enqueueCSS('paysera-payment-css', PayseraPaths::PAYSERA_PAYMENT_CSS);

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
        if (!$this->paymentSettings->isEnabled()) {
            return [];
        }

        PayseraHTMLHelper::enqueueCSS('paysera-payment-css', PayseraPaths::PAYSERA_PAYMENT_CSS);

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
            $this->paymentSettings->isOwnershipCodeEnabled() === true
            && (
                $this->paymentSettings->getOwnershipCode() !== null
                && $this->paymentSettings->getOwnershipCode() !== ''
            )
        ) {
            echo wp_kses(
                '<meta name="verify-paysera" content="' . $this->paymentSettings->getOwnershipCode() . '">',
                ['meta' => ['name' => [], 'content' => []]]
            );
        }
    }

    public function initQualitySign(): void
    {
        if (!did_action(self::SIGN_ASSETS_INIT_ACTION_KEY)) {
            do_action(self::SIGN_ASSETS_INIT_ACTION_KEY);
        }
    }

    public function qualitySignAssets(): void
    {
        if (
            $this->paymentSettings->isQualitySignEnabled()
            && $this->paymentSettings->getProjectId() !== null
            && $this->paymentSettings->isEnabled()
        ) {
            $this->addQualitySignScript($this->paymentSettings->getProjectId());
        }
    }


    public function getDeliveryGateways(): array
    {
        $gateways = [];

        $payseraDeliverySettings = $this->deliverySettingsProvider->getPayseraDeliverySettings();

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
        $payseraDeliverySettings = $this->deliverySettingsProvider->getPayseraDeliverySettings();
        foreach ($payseraDeliverySettings->getDeliveryGateways() as $deliveryGateway => $isEnabled) {
            foreach (PayseraDeliverySettings::DELIVERY_GATEWAY_TYPE_MAP as $deliveryGatewayType) {

                if (!$this->deliveryHelper->deliveryGatewayClassExists($deliveryGateway, $deliveryGatewayType)) {
                    $this->createDeliveryEntity($deliveryGateway, $deliveryGatewayType);
                }

                require_once 'Entity/class-paysera-' . $deliveryGateway . '-' . $deliveryGatewayType . '-delivery.php';
            }
        }
    }

    public function deliveryGatewayLogos(string $label, WC_Shipping_Rate $shippingRate): string
    {
        PayseraHTMLHelper::enqueueCSS('paysera-delivery-css', PayseraPaths::PAYSERA_DELIVERY_CSS);

        if (
            empty($this->deliverySettingsProvider->getPayseraDeliverySettings()->getDeliveryGateways())
            === true
            || $this->deliveryHelper->isPayseraDeliveryGateway($shippingRate->get_method_id()) === false
        ) {
            return $label;
        }

        if ($this->doesShippingRateOfferFreeDelivery($shippingRate)) {
            $label .= sprintf(
                ': <span class="woocommerce-Price-amount amount"><bdi>%s</bdi></span>',
                __('Free', 'woocommerce')
            );
        }

        $error = false;

        $hints = $this->sessionHelper->getData(self::DELIVERY_GATEWAY_WEIGHT_HINT_SESSION_KEY, []);

        if (isset($hints[$shippingRate->get_id()])) {
            $label .= '<p class="paysera-delivery-error">' . $hints[$shippingRate->get_id()] . '</p>';
            $error = true;
        }

        foreach ($this->deliveryHelper->getPayseraDeliveryGateways() as $deliveryGateway) {
            if (
                $shippingRate->get_method_id() === PayseraDeliverySettings::DELIVERY_GATEWAY_PREFIX
                . $deliveryGateway->getCode() . '_courier'
                || $shippingRate->get_method_id() === PayseraDeliverySettings::DELIVERY_GATEWAY_PREFIX
                . $deliveryGateway->getCode() . '_terminals'
            ) {
                if ($error === false) {
                    $label .= '<br>';
                }

                $label .= $this->deliveryAdminHtml->generateDeliveryGatewayLogoHtml($deliveryGateway, true);
            }
        }

        return $label;
    }

    public function initDeliveryGatewayWeightRestrictionsHint(array $rates)
    {
        if (empty($rates)) {
            return;
        }

        if (!current($rates) instanceof WC_Shipping_Rate) {
            $shippingForPackage = WC()->session->get("shipping_for_package_0");
            $rates = $shippingForPackage !== NULL && isset($shippingForPackage['rates']) ? $shippingForPackage['rates'] : [];
        }

        $totalWeight = $this->deliveryHelper->getCartTotalDeliveryWeight();

        $weightValidator = new PayseraDeliveryWeightValidator(
            $this->sessionHelper,
            $this->deliveryHelper,
            $this->deliverySettingsProvider,
        );

        $hints = [];
        foreach ($rates as $shippingRate) {
            if (!$this->deliveryHelper->isPayseraDeliveryGateway($shippingRate->get_method_id())) {
                continue;
            }

            $result = $weightValidator->validateShippingMethod(
                $totalWeight,
                $shippingRate->get_method_id() . ':' . $shippingRate->get_instance_id()
            );

            if ($result['validated'] === false) {
                $hints[$shippingRate->get_id()] = current($result['messages']);
            }
        }

        if (!empty($hints)) {
            $this->sessionHelper->setData(self::DELIVERY_GATEWAY_WEIGHT_HINT_SESSION_KEY, $hints);
        }
    }

    public function applyDeliveryGatewayWeightRestrictionsHint(array $rates): array
    {
        do_action(self::DELIVERY_GATEWAY_WEIGHT_HINT_ACTION_KEY, $rates);

        return $rates;
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
        PayseraHTMLHelper::enqueueJS(
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

        $deliveryGatewayTitles = $this->deliverySettingsProvider->getPayseraDeliverySettings()
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
                $this->deliveryHelper,
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

    public function restrictOrderReceivedFromUnknownClient(): bool
    {
        if (get_current_user_id() > 0) {
            return false;
        }

        $order = wc_get_order(absint(get_query_var( 'order-received')));
        if ($order === false) {
            return true;
        }

        if ($order->get_payment_method() !== WC()->payment_gateways->payment_gateways()['paysera']->id) {
            return true;
        }

        return false;
    }

    private function doesShippingRateOfferFreeDelivery(WC_Shipping_Rate $shippingRate): bool
    {
        $shippingCost = new Money($shippingRate->get_cost());
        return $shippingCost->isZero();
    }
}

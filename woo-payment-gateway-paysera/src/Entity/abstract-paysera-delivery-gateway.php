<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

use Paysera\Action\PayseraDeliveryActions;
use Paysera\DataValidator\Validator\Exception\IncorrectValidationRuleStructure;
use Paysera\Entity\PayseraDeliveryGatewaySettings;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\Exception\ValidationException;
use Paysera\Factory\DeliverySettingsValidatorFactory;
use Paysera\Helper\PostDataHelper;
use Paysera\Provider\ContainerProvider;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliveryGatewayInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliveryGatewaySettingsInterface;
use Paysera\Scoped\Psr\Container\ContainerInterface;
use Paysera\Service\DeliveryLogger;
use Paysera\Validation\PayseraDeliverySettingsClientValidator;
use Paysera\Validation\PayseraDeliverySettingsValidator;

abstract class Paysera_Delivery_Gateway extends WC_Shipping_Method implements PayseraDeliveryGatewayInterface
{
    /**
     * @var string
     */
    protected $deliveryGatewayCode;

    /**
     * @var string
     */
    protected $defaultGatewayName;

    /**
     * @var string
     */
    protected $defaultGatewayType;

    /**
     * @var string
     */
    protected $receiverType;

    /**
     * @var string
     */
    protected $defaultDescription;

    /**
     * @var PayseraDeliveryActions
     */
    private $payseraDeliveryActions;
    /**
     * @var PayseraDeliverySettingsProvider
     */
    private $payseraDeliverySettingsProvider;
    /**
     * @var DeliveryLogger
     */
    private $logger;

    /**
     * @var PostDataHelper
     */
    private $postDataHelper;

    /**
     * @var PayseraDeliverySettingsValidator
     */
    private $backendValidator;

    /**
     * @var PayseraDeliverySettingsClientValidator
     */
    private $clientValidator;
    private ContainerInterface $container;

    public function __construct($instance_id = 0)
    {
        parent::__construct();

        $this->container = (new ContainerProvider())->getContainer();
        $this->payseraDeliveryActions = $this->container->get(PayseraDeliveryActions::class);
        $this->payseraDeliverySettingsProvider = $this->container->get(PayseraDeliverySettingsProvider::class);
        $this->logger = $this->container->get(DeliveryLogger::class);

        $this->id = $this->generateId();
        $this->instance_id = absint($instance_id);
        $this->title = $this->getDefaultTitle();
        $this->method_title = $this->getDefaultTitle();
        $this->method_description = $this->buildMethodDescription();

        $this->init_form_fields();
        $this->init_settings();
        $this->initValidators();

        $this->title = $this->get_option('title');

        $this->payseraDeliveryActions->updateDeliveryGatewaySetting(
            $this->id,
            PayseraDeliverySettings::RECEIVER_TYPE,
            $this->receiverType
        );

        $this->supports = [
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        ];

        add_action('woocommerce_update_options_shipping_' . $this->id, [$this, 'process_admin_options']);
        add_filter('woocommerce_package_rates', [$this, 'hideShippingWeightBased'], 10, 2);
    }

    public function getCode(): string
    {
        return $this->id;
    }

    public function isTestGateway(): bool
    {
        return strpos($this->getCode(), 'paysera_delivery_test_') === 0;
    }

    public function getName(): string
    {
        return $this->get_instance_option('title');
    }

    public function getFee(): float
    {
        return (float)$this->get_instance_option(PayseraDeliverySettings::FEE, PayseraDeliverySettings::DEFAULT_FEE);
    }

    public function getSettings(): PayseraDeliveryGatewaySettingsInterface
    {
        $minimumWeight = $this->get_instance_option(
            PayseraDeliverySettings::MINIMUM_WEIGHT,
            PayseraDeliverySettings::DEFAULT_MINIMUM_WEIGHT
        );
        $maximumWeight = $this->get_instance_option(
            PayseraDeliverySettings::MAXIMUM_WEIGHT,
            PayseraDeliverySettings::DEFAULT_MAXIMUM_WEIGHT
        );
        $senderType = $this->get_instance_option(
            PayseraDeliverySettings::SENDER_TYPE,
            PayseraDeliverySettings::DEFAULT_TYPE
        );

        return (new PayseraDeliveryGatewaySettings())
            ->setMinimumWeight((int)$minimumWeight)
            ->setMaximumWeight((int)$maximumWeight)
            ->setSenderType($senderType)
            ->setReceiverType($this->receiverType)
        ;
    }

    public function calculate_shipping($package = [])
    {
        $rate = [
            'id' => $this->get_rate_id(),
            'label' => $this->title,
            'cost' => $this->instance_settings[PayseraDeliverySettings::FEE],
        ];

        $freeDeliveryLimit = $this->instance_settings[PayseraDeliverySettings::FREE_DELIVERY_LIMIT];

        if ($freeDeliveryLimit > 0 && WC()->cart->get_displayed_subtotal() >= $freeDeliveryLimit) {
            $rate['cost'] = 0;
        }

        $this->add_rate($rate);
    }

    /**
     * @param array<WC_Shipping_Rate>|mixed $rates Shipping rates array or any value from third-party filters
     * @param array<string, mixed> $package Package data from WooCommerce
     *
     * @return array<string, WC_Shipping_Rate>|mixed Returns filtered rates array when input is array,
     *                                                otherwise returns input unchanged for compatibility
     */
    public function hideShippingWeightBased($rates, $package)
    {
        if (!is_array($rates)) {
            $this->logger->error(
                'Invalid shipping rates type in woocommerce_package_rates filter. '
                . 'Expected array, received: ' . gettype($rates) . '. '
                . 'Check for plugin conflicts modifying this filter.'
            );
            return $rates;
        }

        if (
            array_key_exists($this->id, $rates) === false
            || $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isHideShippingMethodsEnabled() === false
        ) {
            return $rates;
        }

        $totalWeight = 0;

        foreach (WC()->cart->cart_contents as $item) {
            $product = wc_get_product($item['product_id']);

            $totalWeight += (float) ($product->get_weight() ?? 0) * (float) $item['quantity'];
        }

        if (get_option('woocommerce_weight_unit') === 'g') {
            $totalWeight /= 1000;
        }

        $minimumWeight = PayseraDeliverySettings::DEFAULT_MINIMUM_WEIGHT;
        $maximumWeight = PayseraDeliverySettings::DEFAULT_MAXIMUM_WEIGHT;

        if (get_option($this->get_instance_option_key()) !== false) {
            $minimumWeight = (float) $this->get_instance_option(PayseraDeliverySettings::MINIMUM_WEIGHT);
            $maximumWeight = (float) $this->get_instance_option(PayseraDeliverySettings::MAXIMUM_WEIGHT);
        }

        if ($totalWeight > $maximumWeight || $totalWeight < $minimumWeight) {
            unset($rates[$this->id]);
        }

        return $rates;
    }

    public function init_form_fields(): void
    {
        $this->instance_form_fields = [
            'title' => [
                'title' => __('Method title', PayseraPaths::PAYSERA_TRANSLATIONS),
                'type' => 'text',
                'description' => __(
                    'This controls the title which the user sees during shipping selection.',
                    PayseraPaths::PAYSERA_TRANSLATIONS
                ),
                'default' => $this->getDefaultTitle(),
                'desc_tip' => true,
            ],
            PayseraDeliverySettings::FEE => [
                'title' => __('Delivery Fee', PayseraPaths::PAYSERA_TRANSLATIONS),
                'type' => PayseraDeliverySettings::FIELD_TYPE_DECIMAL,
                'default' => PayseraDeliverySettings::DEFAULT_FEE,
                'placeholder' => wc_format_localized_price(PayseraDeliverySettings::DEFAULT_FEE),
                'description' => get_woocommerce_currency_symbol(),
                'desc_tip' => true,
            ],
            PayseraDeliverySettings::MINIMUM_WEIGHT => [
                'title' => __('Minimum weight', PayseraPaths::PAYSERA_TRANSLATIONS),
                'type' => PayseraDeliverySettings::FIELD_TYPE_DECIMAL,
                'default' => PayseraDeliverySettings::DEFAULT_MINIMUM_WEIGHT,
                'placeholder' => wc_format_localized_price(PayseraDeliverySettings::DEFAULT_MINIMUM_WEIGHT),
                'description' => __('Kilograms', PayseraPaths::PAYSERA_TRANSLATIONS),
                'desc_tip' => true,
            ],
            PayseraDeliverySettings::MAXIMUM_WEIGHT => [
                'title' => __('Maximum weight', PayseraPaths::PAYSERA_TRANSLATIONS),
                'type' => PayseraDeliverySettings::FIELD_TYPE_DECIMAL,
                'default' => PayseraDeliverySettings::DEFAULT_MAXIMUM_WEIGHT,
                'placeholder' => wc_format_localized_price(PayseraDeliverySettings::DEFAULT_MAXIMUM_WEIGHT),
                'description' => __('Kilograms', PayseraPaths::PAYSERA_TRANSLATIONS),
                'desc_tip' => true,
            ],
            PayseraDeliverySettings::SENDER_TYPE => [
                'title' => __('Preferred pickup type', PayseraPaths::PAYSERA_TRANSLATIONS),
                'type' => 'select',
                'class' => 'wc-enhanced-select',
                'default' => PayseraDeliverySettings::TYPE_COURIER,
                'options' => $this->getSenderTypeOptions(),
            ],
            PayseraDeliverySettings::FREE_DELIVERY_LIMIT => [
                'title' => __('Minimum order amount for free shipping', PayseraPaths::PAYSERA_TRANSLATIONS),
                'type' => PayseraDeliverySettings::FIELD_TYPE_DECIMAL,
                'placeholder' => wc_format_localized_price(PayseraDeliverySettings::DEFAULT_FREE_DELIVERY_LIMIT),
                'description' => __(
                    'Users will need to spend this amount to get free shipping.',
                    PayseraPaths::PAYSERA_TRANSLATIONS
                ),
                'default' => PayseraDeliverySettings::DEFAULT_FREE_DELIVERY_LIMIT,
                'desc_tip' => true,
            ],
        ];
    }

    public function is_available($package): bool
    {
        return
            parent::is_available($package)
            && $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->isEnabled()
            ;
    }

    //region Validation

    /**
     * @throws IncorrectValidationRuleStructure
     */
    public function initValidators(): void
    {
        $validatorFactory = $this->container->get(DeliverySettingsValidatorFactory::class);
        $this->backendValidator = $validatorFactory->createBackendValidator($this);
        $this->clientValidator = $validatorFactory->createClientValidator($this);
        $this->postDataHelper = $this->container->get(PostDataHelper::class);
    }

    public function process_admin_options(): bool
    {
        $post_data = $this->get_post_data();

        $post_data = $this->postDataHelper->normalizeDecimalSeparator(
            $post_data,
            $this->getDecimalFieldsNames(),
            $this->getValidationOptions()[PayseraDeliverySettings::OPTION_DECIMAL_SEPARATOR]
        );

        $this->data = $post_data;

        return parent::process_admin_options();
    }

    public function getValidationRules(): array
    {
        return [
            PayseraDeliverySettings::FEE => 'is-number|min:0',
            PayseraDeliverySettings::FREE_DELIVERY_LIMIT => 'is-number|min:0',
            PayseraDeliverySettings::MINIMUM_WEIGHT => 'is-number|min:0|less-or-equals:'
                . PayseraDeliverySettings::MAXIMUM_WEIGHT,
            PayseraDeliverySettings::MAXIMUM_WEIGHT => 'is-number|min:0|greater-or-equals:'
                . PayseraDeliverySettings::MINIMUM_WEIGHT,
        ];
    }

    public function getValidationFieldsTitles(): array
    {
        return [
            PayseraDeliverySettings::FEE => __('Delivery Fee', PayseraPaths::PAYSERA_TRANSLATIONS),
            PayseraDeliverySettings::MINIMUM_WEIGHT => __('Minimum weight', PayseraPaths::PAYSERA_TRANSLATIONS),
            PayseraDeliverySettings::MAXIMUM_WEIGHT => __('Maximum weight', PayseraPaths::PAYSERA_TRANSLATIONS),
            PayseraDeliverySettings::FREE_DELIVERY_LIMIT => __(
                'Minimum order amount for free shipping',
                PayseraPaths::PAYSERA_TRANSLATIONS
            ),
        ];
    }

    public function getValidationErrorsTemplates(): array
    {
        $decimal = (!empty(wc_get_price_decimal_separator()))
            ? wc_get_price_decimal_separator()
            : PayseraDeliverySettings::OPTION_DECIMAL_SEPARATOR;

        return [
            PayseraDeliverySettings::VALIDATION_ERROR_MIN_VALUE => __(
                ':attribute should be greater than or equal to :min.',
                PayseraPaths::PAYSERA_TRANSLATIONS
            ),
            PayseraDeliverySettings::VALIDATION_ERROR_GRATER_OR_EQUALS => __(
                ':attribute cannot be less than :fieldToCompare.',
                PayseraPaths::PAYSERA_TRANSLATIONS
            ),
            PayseraDeliverySettings::VALIDATION_ERROR_LESS_OR_EQUALS => __(
                ':attribute cannot be greater than :fieldToCompare.',
                PayseraPaths::PAYSERA_TRANSLATIONS
            ),
            PayseraDeliverySettings::VALIDATION_ERROR_IS_NUMBER => __(':attribute value should be a number.', PayseraPaths::PAYSERA_TRANSLATIONS),
            PayseraDeliverySettings::VALIDATION_ERROR_INVALID_DECIMAL_SEPARATOR => sprintf(
                __('Please enter a value with one decimal point (%s) without thousand separators.', 'woocommerce'),
                $decimal
            ),
        ];
    }

    public function getValidationOptions(): array
    {
        return [
            PayseraDeliverySettings::OPTION_DECIMAL_SEPARATOR => wc_get_price_decimal_separator(),
        ];
    }

    public function getDecimalFieldsNames(): array
    {
        return [
            $this->get_field_key(PayseraDeliverySettings::FEE),
            $this->get_field_key(PayseraDeliverySettings::FREE_DELIVERY_LIMIT),
            $this->get_field_key(PayseraDeliverySettings::MAXIMUM_WEIGHT),
            $this->get_field_key(PayseraDeliverySettings::MINIMUM_WEIGHT),
        ];
    }

    /**
     * @throws ValidationException
     * @param mixed $fieldName
     * @param mixed $value
     */
    public function validate_paysera_decimal_field($fieldName, $value)
    {
        $postData = $this->postDataHelper->trimPostDataKeysPrefix($this->data, $this->plugin_id . $this->id . '_');

        $this->backendValidator->validateFieldValueOrFail(
            $fieldName,
            $postData,
            $this->data[$this->get_field_key('title')] ?? null
        );

        return $value;
    }

    public function generate_paysera_decimal_html($key, $data)
    {
        return $this->clientValidator->generateValidatableField($key, $data, $this);
    }

    //endregion

    private function buildMethodDescription(): string
    {
        $minimumWeight = PayseraDeliverySettings::DEFAULT_MINIMUM_WEIGHT;
        $maximumWeight = PayseraDeliverySettings::DEFAULT_MAXIMUM_WEIGHT;
        $fee = PayseraDeliverySettings::DEFAULT_FEE;
        $preferredPickupType = PayseraDeliverySettings::DEFAULT_TYPE;

        if (get_option($this->get_instance_option_key()) !== false) {
            $minimumWeight = (float) $this->get_instance_option(PayseraDeliverySettings::MINIMUM_WEIGHT);
            $maximumWeight = (float) $this->get_instance_option(PayseraDeliverySettings::MAXIMUM_WEIGHT);
            $fee = (float) $this->get_instance_option(PayseraDeliverySettings::FEE);
            $preferredPickupType = $this->get_instance_option(PayseraDeliverySettings::SENDER_TYPE);

            $this->payseraDeliveryActions->updateDeliveryGatewaySetting(
                $this->id,
                PayseraDeliverySettings::MINIMUM_WEIGHT,
                $minimumWeight
            );
            $this->payseraDeliveryActions->updateDeliveryGatewaySetting(
                $this->id,
                PayseraDeliverySettings::MAXIMUM_WEIGHT,
                $maximumWeight
            );
            $this->payseraDeliveryActions->updateDeliveryGatewaySetting(
                $this->id,
                PayseraDeliverySettings::SENDER_TYPE,
                $preferredPickupType
            );
        }

        return sprintf(
            __($this->defaultDescription, PayseraPaths::PAYSERA_TRANSLATIONS),
            $this->getDeliveryGatewayTitle()
        )
            . ' '
            . $this->buildExtraDescription($minimumWeight, $maximumWeight, $fee, $preferredPickupType)
        ;
    }

    private function buildExtraDescription(
        float $minimumWeight,
        float $maximumWeight,
        float $fee,
        string $preferredPickupType
    ): string {
        $extraDescription = '';

        if ($maximumWeight > 0) {
            $extraDescription .= $this->prepareExtraDescriptionText(
                __('Allowed weight:', PayseraPaths::PAYSERA_TRANSLATIONS),
                sprintf(
                    '%0.2f-%0.2fkg',
                    $minimumWeight,
                    $maximumWeight
                )
            );
        }

        if ($fee > 0) {
            $extraDescription .= $this->prepareExtraDescriptionText(
                __('Delivery Fee:', PayseraPaths::PAYSERA_TRANSLATIONS),
                sprintf(
                    '%0.2f%s',
                    $fee,
                    get_woocommerce_currency_symbol()
                )
            );
        }

        $extraDescription .= $this->prepareExtraDescriptionText(
            __('Preferred pickup type:', PayseraPaths::PAYSERA_TRANSLATIONS),
            sprintf(
                '%s',
                __(PayseraDeliverySettings::READABLE_TYPES[$preferredPickupType], PayseraPaths::PAYSERA_TRANSLATIONS)
            )
        );

        return $extraDescription;
    }

    private function prepareExtraDescriptionText(string $title, string $description): string
    {
        if (version_compare(WC_VERSION, '8.4.0', '>=')) {
            return sprintf('%s %s. ', $title, $description);
        }
        return sprintf(
            '<div class="paysera-delivery-extra-description"><strong>%s</strong> %s</div>',
            $title,
            $description
        );

    }

    private function getDeliveryGatewayTitle(): string
    {
        return trim(
            str_replace(
                [
                    __('Terminals', PayseraPaths::PAYSERA_TRANSLATIONS),
                    __('Courier', PayseraPaths::PAYSERA_TRANSLATIONS),
                ],
                '',
                $this->getDefaultTitle()
            )
        );
    }

    private function getSenderTypeOptions(): array
    {
        $shipmentMethods = $this->payseraDeliverySettingsProvider->getPayseraDeliverySettings()->getShipmentMethods();

        $senderTypes = [];
        $companyCode = explode('_', $this->deliveryGatewayCode)[0];
        $isParcelMachineAvailable = !in_array($companyCode, PayseraDeliverySettings::PARCEL_MACHINE_DISABLED_DELIVERY_GATEWAYS, true);

        if ($this->receiverType === PayseraDeliverySettings::TYPE_COURIER) {
            if ($shipmentMethods[PayseraDeliverySettings::SHIPMENT_METHOD_COURIER_2_COURIER] === true) {
                $senderTypes[PayseraDeliverySettings::TYPE_COURIER] = __('Courier', PayseraPaths::PAYSERA_TRANSLATIONS);
            }

            if ($shipmentMethods[PayseraDeliverySettings::SHIPMENT_METHOD_PARCEL_MACHINE_2_COURIER] === true && $isParcelMachineAvailable) {
                $senderTypes[PayseraDeliverySettings::TYPE_PARCEL_MACHINE] =
                    __('Parcel locker', PayseraPaths::PAYSERA_TRANSLATIONS)
                ;
            }
        } elseif ($this->receiverType === PayseraDeliverySettings::TYPE_PARCEL_MACHINE && $isParcelMachineAvailable) {
            if ($shipmentMethods[PayseraDeliverySettings::SHIPMENT_METHOD_COURIER_2_PARCEL_MACHINE] === true) {
                $senderTypes[PayseraDeliverySettings::TYPE_COURIER] = __('Courier', PayseraPaths::PAYSERA_TRANSLATIONS);
            }

            if (
                $shipmentMethods[PayseraDeliverySettings::SHIPMENT_METHOD_PARCEL_MACHINE_2_PARCEL_MACHINE]
                === true
            ) {
                $senderTypes[PayseraDeliverySettings::TYPE_PARCEL_MACHINE] =
                    __('Parcel locker', PayseraPaths::PAYSERA_TRANSLATIONS)
                ;
            }
        }

        return $senderTypes;
    }

    private function generateId(): string
    {
        return PayseraDeliverySettings::DELIVERY_GATEWAY_PREFIX . $this->deliveryGatewayCode;
    }

    private function getDefaultTitle(): string
    {
        return sprintf('%s %s', $this->defaultGatewayName, __($this->defaultGatewayType, 'paysera'));
    }
}

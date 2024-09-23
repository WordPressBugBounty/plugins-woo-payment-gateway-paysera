<?php

declare(strict_types=1);

namespace Paysera\Entity;

defined('ABSPATH') || exit;

class PayseraDeliverySettings
{
    public const SETTINGS_NAME = 'paysera_delivery_settings';
    public const EXTRA_SETTINGS_NAME = 'paysera_delivery_extra_settings';
    public const DELIVERY_GATEWAYS_SETTINGS_NAME = 'paysera_delivery_gateways_settings';
    public const DELIVERY_GATEWAYS_TITLES = 'paysera_delivery_gateways_titles';

    public const ENABLED = 'delivery_enabled';
    public const PROJECT_ID = 'project_id';
    public const RESOLVED_PROJECT_ID = 'resolved_project_id';
    public const PROJECT_PASSWORD = 'project_password';
    public const TEST_MODE = 'test_mode';
    public const HOUSE_NUMBER_FIELD = 'house_number_field';
    public const GRID_VIEW = 'grid_view';
    public const HIDE_SHIPPING_METHODS = 'hide_shipping_methods';
    public const LOG_LEVEL = 'log_level';
    public const DELIVERY_GATEWAYS = 'delivery_gateways';
    public const SHIPMENT_METHODS = 'shipment_methods';

    public const MINIMUM_WEIGHT = 'minimum_weight';
    public const MAXIMUM_WEIGHT = 'maximum_weight';
    public const SENDER_TYPE = 'sender_type';
    public const RECEIVER_TYPE = 'receiver_type';
    public const FEE = 'fee';
    public const FREE_DELIVERY_LIMIT = 'free_delivery_limit';
    public const TERMINAL_COUNTRY = 'paysera_terminal_country';
    public const TERMINAL_CITY = 'paysera_terminal_city';
    public const TERMINAL = 'terminal';
    public const TERMINAL_LOCATION = 'paysera_terminal_location';
    public const BILLING_HOUSE_NO = 'billing_house_no';
    public const SHIPPING_HOUSE_NO = 'shipping_house_no';

    public const DEFAULT_MINIMUM_WEIGHT = 0;
    public const DEFAULT_MAXIMUM_WEIGHT = 30;
    public const DEFAULT_FEE = 0;
    public const DEFAULT_TYPE = self::TYPE_COURIER;
    public const DEFAULT_FREE_DELIVERY_LIMIT = 0;

    public const SHIPMENT_METHOD_COURIER_2_COURIER = 'courier2courier';
    public const SHIPMENT_METHOD_COURIER_2_PARCEL_MACHINE = 'courier2parcel-machine';
    public const SHIPMENT_METHOD_PARCEL_MACHINE_2_COURIER = 'parcel-machine2courier';
    public const SHIPMENT_METHOD_PARCEL_MACHINE_2_PARCEL_MACHINE = 'parcel-machine2parcel-machine';

    public const TYPE_COURIER = 'courier';
    public const TYPE_PARCEL_MACHINE = 'parcel-machine';
    public const TYPE_TERMINALS = 'terminals';

    public const VALIDATION_ERROR_MIN_VALUE = 'minVal';
    public const VALIDATION_ERROR_GRATER_OR_EQUALS = 'greaterOrEquals';
    public const VALIDATION_ERROR_LESS_OR_EQUALS = 'lessOrEquals';
    public const VALIDATION_ERROR_IS_NUMBER = 'isNumber';
    public const VALIDATION_ERROR_INVALID_DECIMAL_SEPARATOR = 'i18n_decimal_error';

    public const FIELD_TYPE_DECIMAL = 'paysera_decimal';

    public const DELIVERY_GATEWAY_TYPE_MAP = [
        self::TYPE_COURIER,
        self::TYPE_TERMINALS,
    ];

    public const READABLE_TYPES = [
        self::TYPE_COURIER => 'Courier',
        self::TYPE_PARCEL_MACHINE => 'Parcel locker',
    ];

    public const PARCEL_MACHINE_DISABLED_DELIVERY_GATEWAYS = [
        'tnt',
        'itella',
    ];

    public const DELIVERY_GATEWAY_PREFIX = 'paysera_delivery_';

    public const OPTION_DECIMAL_SEPARATOR = 'decimalSeparator';

    public const DELIVERY_ORDER_EVENT_UPDATED = 'order_updated';

    public const WC_ORDER_EVENT_CREATED = 'wc_order_created';

    public const ORDER_META_KEY_HOUSE_NO = '_shipping_house_no';

    public const DELIVERY_ORDER_ID_META_KEY = '_paysera_delivery_order_api_id';
    public const DELIVERY_ORDER_NUMBER_META_KEY = '_paysera_delivery_order_id';
    public const DELIVERY_ORDER_TERMINAL_COUNTRY_META_KEY = '_paysera_delivery_order_terminal_country';
    public const DELIVERY_ORDER_TERMINAL_CITY_META_KEY = '_paysera_delivery_order_terminal_city';
    public const DELIVERY_ORDER_TERMINAL_KEY = '_paysera_delivery_order_terminal_id';

    private ?bool $enabled;
    private ?int $projectId;
    private ?string $resolvedProjectId;
    private ?string $projectPassword;
    private ?bool $testModeEnabled;
    private ?bool $houseNumberFieldEnabled;
    private ?bool $gridViewEnabled;
    private ?bool $hideShippingMethodsEnabled;
    private ?string $logLevel;
    private array $deliveryGateways;
    private array $deliveryGatewayTitles;
    private array $shipmentMethods;

    public function __construct()
    {
        $this->enabled = null;
        $this->projectId = null;
        $this->resolvedProjectId = null;
        $this->projectPassword = null;
        $this->testModeEnabled = null;
        $this->houseNumberFieldEnabled = null;
        $this->gridViewEnabled = null;
        $this->hideShippingMethodsEnabled = null;
        $this->logLevel = null;
        $this->deliveryGateways = [];
        $this->deliveryGatewayTitles = [];
        $this->shipmentMethods = [];
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(?bool $enabled): PayseraDeliverySettings
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getProjectId(): ?int
    {
        return $this->projectId;
    }

    public function setProjectId(?int $projectId): self
    {
        $this->projectId = $projectId;

        return $this;
    }

    public function getResolvedProjectId(): ?string
    {
        return $this->resolvedProjectId;
    }

    public function setResolvedProjectId(string $resolvedProjectId): self
    {
        $this->resolvedProjectId = $resolvedProjectId;

        return $this;
    }

    public function getProjectPassword(): ?string
    {
        return $this->projectPassword;
    }

    public function setProjectPassword(string $projectPassword): self
    {
        $this->projectPassword = $projectPassword;

        return $this;
    }

    public function isTestModeEnabled(): ?bool
    {
        return $this->testModeEnabled;
    }

    public function setTestModeEnabled(bool $testModeEnabled): self
    {
        $this->testModeEnabled = $testModeEnabled;

        return $this;
    }

    public function isHouseNumberFieldEnabled(): ?bool
    {
        return $this->houseNumberFieldEnabled;
    }

    public function setHouseNumberFieldEnabled(bool $houseNumberFieldEnabled): self
    {
        $this->houseNumberFieldEnabled = $houseNumberFieldEnabled;

        return $this;
    }

    public function isGridViewEnabled(): ?bool
    {
        return $this->gridViewEnabled;
    }

    public function setGridViewEnabled(bool $gridViewEnabled): self
    {
        $this->gridViewEnabled = $gridViewEnabled;

        return $this;
    }

    public function isHideShippingMethodsEnabled(): ?bool
    {
        return $this->hideShippingMethodsEnabled;
    }

    public function setHideShippingMethodsEnabled(bool $hideShippingMethodsEnabled): self
    {
        $this->hideShippingMethodsEnabled = $hideShippingMethodsEnabled;

        return $this;
    }

    public function getLogLevel(): ?string
    {
        return $this->logLevel;
    }

    public function setLogLevel(string $logLevel): self
    {
        $this->logLevel = $logLevel;

        return $this;
    }

    public function getDeliveryGateways(): array
    {
        return $this->deliveryGateways;
    }

    public function setDeliveryGateways(array $deliveryGateways): self
    {
        $this->deliveryGateways = $deliveryGateways;

        return $this;
    }

    public function getDeliveryGatewayTitles(): array
    {
        return $this->deliveryGatewayTitles;
    }

    public function setDeliveryGatewayTitles(array $deliveryGatewayTitles): self
    {
        $this->deliveryGatewayTitles = $deliveryGatewayTitles;

        return $this;
    }

    public function getShipmentMethods(): array
    {
        return $this->shipmentMethods;
    }

    public function setShipmentMethods(array $shipmentMethods): self
    {
        $this->shipmentMethods = $shipmentMethods;

        return $this;
    }
}

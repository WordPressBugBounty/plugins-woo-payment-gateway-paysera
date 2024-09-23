<?php

declare(strict_types=1);

namespace Paysera\Action;

defined('ABSPATH') || exit;

use Exception;
use Paysera\Admin\PayseraDeliveryAdmin;
use Paysera\DeliveryApi\MerchantClient\MerchantClient;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Provider\MerchantClientProviderInterface;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Scoped\Paysera\Component\RestClientCommon\Entity\Filter;
use Paysera\Service\LoggerInterface;
use WC_Cache_Helper;

class PayseraDeliveryActions
{
    private MerchantClientProviderInterface $merchantClientProvider;
    private LoggerInterface $logger;
    private PayseraDeliverySettingsProvider $deliverySettingsProvider;

    public function __construct(
        MerchantClientProviderInterface $merchantClientProvider,
        LoggerInterface $logger,
        PayseraDeliverySettingsProvider $deliverySettingsProvider
    ) {
        $this->merchantClientProvider = $merchantClientProvider;
        $this->logger = $logger;
        $this->deliverySettingsProvider = $deliverySettingsProvider;
    }

    public function build(): void
    {
        add_action('admin_post_paysera_delivery_gateway_change', [$this, 'changeDeliveryGatewayStatus']);
        add_action('admin_post_paysera_delivery_change_status', [$this, 'changeDeliveryStatus']);
        add_action('add_option', [$this, 'validateSettingsBeforeCreate'], 10, 2);
        add_action('update_option', [$this, 'validateSettingsBeforeUpdate'], 10, 3);
        add_filter('removable_query_args', [$this, 'filterRemovableQueryArgs']);
    }

    public function changeDeliveryGatewayStatus(): void
    {
        $this->updateDeliveryGatewayStatus(
            sanitize_text_field(wp_unslash($_GET['gateway'])),
            sanitize_text_field(wp_unslash($_GET['change'])) === 'enable'
        );

        wp_redirect(
            'admin.php?page=paysera-delivery&tab=' . PayseraDeliveryAdmin::TAB_DELIVERY_GATEWAYS_LIST_SETTINGS
        );
    }

    public function changeDeliveryStatus(): void
    {
        if (!$this->isReadyForEnabling()) {
            wp_redirect('admin.php?page=paysera-delivery&enabled_massage=yes');
            exit();
        }

        $this->updateSettingsOption(
            PayseraDeliverySettings::ENABLED,
            sanitize_text_field(wp_unslash($_GET['status'])) === 'enable' ? 'yes' : 'no'
        );

        wp_redirect('admin.php?page=paysera-delivery');
    }

    /**
     * @param array $deliveryGateways
     */
    public function setDeliveryGatewayTitles(array $deliveryGateways): void
    {
        foreach ($deliveryGateways as $deliveryGateway) {
            $this->updateDeliveryGatewayTitle($deliveryGateway->getCode(), $deliveryGateway->getDescription());
        }
    }

    /**
     * @param array $deliveryGateways
     */
    public function reSyncDeliveryGatewayStatus(array $deliveryGateways): void
    {
        foreach ($deliveryGateways as $deliveryGateway) {
            if ($deliveryGateway->isEnabled() === false) {
                $this->updateDeliveryGatewayStatus($deliveryGateway->getCode(), false);
            }
        }
    }

    /**
     * @param array $shipmentMethods
     */
    public function syncShipmentMethodsStatus(array $shipmentMethods): void
    {
        foreach ($shipmentMethods as $shipmentMethod) {
            $this->updateShipmentMethodStatus($shipmentMethod->getCode(), $shipmentMethod->isEnabled());
        }
    }

    public function updateDeliveryGatewaySetting(
        string $deliveryGatewayCode,
        string $settingName,
        $settingValue
    ): void {
        $this->updateOptions($deliveryGatewayCode . '_' . $settingName, $settingValue);
    }

    public function updateResolvedProjectId(string $projectId): void
    {
        $this->updateOptions(PayseraDeliverySettings::RESOLVED_PROJECT_ID, $projectId);
    }

    private function updateOptions(string $optionName, $optionValue): void
    {
        $options = get_option(PayseraDeliverySettings::DELIVERY_GATEWAYS_SETTINGS_NAME, []);

        $options[$optionName] = $optionValue;

        update_option(PayseraDeliverySettings::DELIVERY_GATEWAYS_SETTINGS_NAME, $options);
    }

    public function updateSettingsOption(string $optionName, $optionValue): void
    {
        $options = get_option(PayseraDeliverySettings::SETTINGS_NAME, []);

        $options[$optionName] = $optionValue;

        update_option(PayseraDeliverySettings::SETTINGS_NAME, $options);
    }

    public function updateExtraSettingsOption(string $optionName, $optionValue): void
    {
        $options = get_option(PayseraDeliverySettings::EXTRA_SETTINGS_NAME, []);

        $options[$optionName] = $optionValue;

        update_option(PayseraDeliverySettings::EXTRA_SETTINGS_NAME, $options);
    }

    private function updateDeliveryGatewayStatus(string $deliveryGateway, bool $isEnabled): void
    {
        $options = get_option(PayseraDeliverySettings::DELIVERY_GATEWAYS_SETTINGS_NAME, []);

        $options[PayseraDeliverySettings::DELIVERY_GATEWAYS][$deliveryGateway] = $isEnabled;

        WC_Cache_Helper::get_transient_version('shipping', true);

        update_option(PayseraDeliverySettings::DELIVERY_GATEWAYS_SETTINGS_NAME, $options);
    }

    private function updateDeliveryGatewayTitle(string $deliveryGateway, string $title): void
    {
        $options = get_option(PayseraDeliverySettings::DELIVERY_GATEWAYS_TITLES, []);

        $options[PayseraDeliverySettings::DELIVERY_GATEWAYS][$deliveryGateway] = $title;

        update_option(PayseraDeliverySettings::DELIVERY_GATEWAYS_TITLES, $options);
    }

    private function updateShipmentMethodStatus(string $shipmentMethod, bool $isEnabled): void
    {
        $options = get_option(PayseraDeliverySettings::DELIVERY_GATEWAYS_SETTINGS_NAME, []);

        $options[PayseraDeliverySettings::SHIPMENT_METHODS][$shipmentMethod] = $isEnabled;

        update_option(PayseraDeliverySettings::DELIVERY_GATEWAYS_SETTINGS_NAME, $options);
    }

    public function filterRemovableQueryArgs(array $removableQueryArgs): array
    {
        $removableQueryArgs[] = 'invalid-credentials';

        return $removableQueryArgs;
    }

    public function validateSettingsBeforeCreate(string $option, $value): void
    {
        $this->checkInvalidCredential($option, $value);
    }

    public function validateSettingsBeforeUpdate(string $option, $oldValue, $value): void
    {
        $this->checkInvalidCredential($option, $value);
    }

    private function checkInvalidCredential(string $option, $value): void
    {
        if (
            $option !== PayseraDeliverySettings::SETTINGS_NAME
            || (isset($value[PayseraDeliverySettings::ENABLED]) && $value[PayseraDeliverySettings::ENABLED] === 'no')
            || (isset($_REQUEST['invalid-credentials']) && $_REQUEST['invalid-credentials'] === 'yes')
        ) {
            return;
        }

        $projectId = $value['project_id'] ?? null;
        $projectPassword = $value['project_password'] ?? null;

        if ($projectId && $projectPassword) {
            $merchantClient = $this->merchantClientProvider->getMerchantClient((int) $projectId, $projectPassword);

            if ($merchantClient !== null) {
                $resolvedProjectId = $this->getResolvedProjectId($merchantClient);

                if ($resolvedProjectId !== null) {
                    $this->updateResolvedProjectId($resolvedProjectId);
                    return;
                }
            }
        }

        wp_safe_redirect('admin.php?page=paysera-delivery&invalid-credentials=yes');
        exit;
    }

    private function getResolvedProjectId(MerchantClient $merchantClient): ?string
    {
        $resolvedProjectId = null;

        try {
            $resolvedProjectId = $merchantClient->getProjects(new Filter())->getList()[0]->getId();
        } catch (Exception $exception) {
            $this->logger->error('Cannot resolve delivery project id', $exception);
        }

        return $resolvedProjectId;
    }

    private function isReadyForEnabling(): bool
    {
        if (!isset($_GET['status'])) {
            return false;
        }

        if (sanitize_text_field(wp_unslash($_GET['status'])) === 'disable') {
            return true;
        }

        $deliverySettings = $this->deliverySettingsProvider->getPayseraDeliverySettings();

        return !empty($deliverySettings->getProjectId()) && !empty($deliverySettings->getProjectPassword());
    }
}

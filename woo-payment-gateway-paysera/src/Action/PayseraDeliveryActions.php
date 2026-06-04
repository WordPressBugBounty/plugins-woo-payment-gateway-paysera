<?php

declare(strict_types=1);

namespace Paysera\Action;

defined('ABSPATH') || exit;

use Exception;
use Paysera\Admin\PayseraDeliveryAdmin;
use Paysera\DeliveryApi\MerchantClient\Entity\ProjectCredentials;
use Paysera\DeliveryApi\MerchantClient\MerchantClient;
use Paysera\Exception\RedirectStopException;
use Paysera\Handler\DeliveryCredentialErrorHandler;
use Paysera\Scoped\Paysera\DeliverySdk\Client\DeliveryApiClient;
use Paysera\Scoped\Paysera\DeliverySdk\Client\Provider\MerchantClientProvider;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\CredentialsValidationException;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\MerchantClientNotFoundException;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\RateLimitExceededException;
use Paysera\Scoped\Paysera\DeliverySdk\Service\DeliveryLoggerInterface;
use Paysera\Dto\PayseraSettingsValidationDto;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\Entity\TransientNotice;
use Paysera\Helper\SecurityHelper;
use Paysera\Provider\DeliveryNoticeMessageProvider;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Service\TransientNoticeMessenger;
use Paysera\Scoped\Paysera\Component\RestClientCommon\Entity\Filter;
use Throwable;
use WC_Cache_Helper;

class PayseraDeliveryActions
{
    private MerchantClientProvider $merchantClientProvider;
    private DeliveryLoggerInterface $logger;
    private PayseraDeliverySettingsProvider $deliverySettingsProvider;
    private DeliveryApiClient $deliveryApiClient;
    private DeliveryCredentialErrorHandler $errorHandler;
    private TransientNoticeMessenger $noticeMessenger;
    private DeliveryNoticeMessageProvider $messageProvider;
    private SecurityHelper $securityHelper;

    public function __construct(
        MerchantClientProvider $merchantClientProvider,
        DeliveryLoggerInterface $logger,
        PayseraDeliverySettingsProvider $deliverySettingsProvider,
        DeliveryApiClient $deliveryApiClient,
        DeliveryCredentialErrorHandler $errorHandler,
        TransientNoticeMessenger $noticeMessenger,
        DeliveryNoticeMessageProvider $messageProvider,
        SecurityHelper $securityHelper = null
    ) {
        $this->merchantClientProvider = $merchantClientProvider;
        $this->logger = $logger;
        $this->deliverySettingsProvider = $deliverySettingsProvider;
        $this->deliveryApiClient = $deliveryApiClient;
        $this->errorHandler = $errorHandler;
        $this->noticeMessenger = $noticeMessenger;
        $this->messageProvider = $messageProvider;
        $this->securityHelper = $securityHelper ?? new SecurityHelper();
    }

    public function build(): void
    {
        add_action('admin_post_paysera_delivery_gateway_change', [$this, 'changeDeliveryGatewayStatus']);
        add_action('admin_post_paysera_delivery_change_status', [$this, 'changeDeliveryStatus']);
        add_action('add_option', [$this, 'validateSettingsBeforeCreate'], 10, 2);
        add_action('update_option', [$this, 'validateSettingsBeforeUpdate'], 10, 3);
    }

    public function changeDeliveryGatewayStatus(): void
    {
        $this->securityHelper->validateAdminRequest('paysera_delivery_gateway_change');

        $gateway = $this->getValidatedGatewayParameter();
        $action = $this->securityHelper->getValidatedActionParameter('change');

        $this->updateDeliveryGatewayStatus($gateway, $action === 'enable');

        // @phpstan-ignore-next-line
        wp_redirect(
            'admin.php?page=paysera-delivery&tab=' . PayseraDeliveryAdmin::TAB_DELIVERY_GATEWAYS_LIST_SETTINGS
        );
    }

    public function changeDeliveryStatus(): void
    {
        $this->securityHelper->validateAdminRequest('paysera_delivery_change_status');

        $action = $this->securityHelper->getValidatedActionParameter('status');

        if (!$this->isReadyForEnabling($action)) {
            $this->errorHandler->handleEnableWithoutCredentials();
        }

        $this->updateSettingsOption(
            PayseraDeliverySettings::ENABLED,
            $action === 'enable' ? 'yes' : 'no'
        );

        // @phpstan-ignore-next-line
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

    /**
     * @throws RedirectStopException
     */
    public function validateSettingsBeforeCreate(string $option, $value): void
    {
        $this->checkInvalidCredential($option, $value, null);
    }

    /**
     * @throws RedirectStopException
     */
    public function validateSettingsBeforeUpdate(string $option, $oldValue, $value): void
    {
        $this->checkInvalidCredential($option, $value, $oldValue);
    }

    /**
     * @throws RedirectStopException
     */
    private function checkInvalidCredential(string $option, $value, $oldValue): void
    {
        if ($option !== PayseraDeliverySettings::SETTINGS_NAME || !is_array($value)) {
            return;
        }

        // Skip validation if value doesn't contain credential keys at all
        if (!array_key_exists('project_id', $value) && !array_key_exists('project_password', $value)) {
            return;
        }

        $projectId = $value['project_id'] ?? null;
        $projectPassword = $value['project_password'] ?? null;
        $oldProjectId = $oldValue['project_id'] ?? null;
        $oldProjectPassword = $oldValue['project_password'] ?? null;

        if ($oldValue !== null && $projectId === $oldProjectId && $projectPassword === $oldProjectPassword) {
            return;
        }

        if ($projectId === null || $projectId === '' || $projectPassword === null || $projectPassword === '') {
            $this->errorHandler->handleEmptyCredentials();
            return; // @phpstan-ignore-line
        }

        $projectCredentials = new ProjectCredentials([
            'project_id' => $projectId,
            'password' => $projectPassword,
        ]);

        try {
            $isValid = $this->deliveryApiClient->validateCredentials($projectCredentials);

            if ($isValid) {
                $this->handleSuccessfulValidation($projectId, $projectPassword);
                return;
            }

            $this->errorHandler->handleInvalidCredentials($projectId);
            return; // @phpstan-ignore-line
        } catch (CredentialsValidationException $exception) {
            $this->errorHandler->handleValidationException($exception);
            return; // @phpstan-ignore-line
        } catch (MerchantClientNotFoundException $exception) {
            $this->errorHandler->handleMerchantNotFound($exception);
            return; // @phpstan-ignore-line
        } catch (RateLimitExceededException $exception) {
            $this->errorHandler->handleRateLimitExceeded($exception);
            return; // @phpstan-ignore-line
        } catch (RedirectStopException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->errorHandler->handleUnexpectedError($exception);
            return; // @phpstan-ignore-line
        }
    }

    private function handleSuccessfulValidation(string $projectId, string $projectPassword): void
    {
        $settings = new PayseraSettingsValidationDto($projectId, $projectPassword);
        $merchantClient = $this->merchantClientProvider->getMerchantClient($settings);
        $resolvedProjectId = $this->getResolvedProjectId($merchantClient);

        if ($resolvedProjectId !== null) {
            $this->updateResolvedProjectId($resolvedProjectId);
        }
    }

    private function getResolvedProjectId(MerchantClient $merchantClient): ?string
    {
        $resolvedProjectId = null;

        try {
            /** @phpstan-ignore-next-line */
            $resolvedProjectId = $merchantClient->getProjects(new Filter())->getList()[0]->getId();
        } catch (Exception $exception) {
            $this->logger->error(
                PayseraPaths::PAYSERA_MESSAGE . ' Cannot resolve delivery project ID: ' . $exception->getMessage()
            );
        }

        return $resolvedProjectId;
    }

    private function isReadyForEnabling(string $action): bool
    {
        if ($action === 'disable') {
            return true;
        }

        $deliverySettings = $this->deliverySettingsProvider->getPayseraDeliverySettings();

        return $deliverySettings->getProjectId() !== ''
            && $deliverySettings->getProjectId() !== null
            && $deliverySettings->getProjectPassword() !== ''
            && $deliverySettings->getProjectPassword() !== null
        ;
    }

    private function getValidatedGatewayParameter(): string
    {
        if (!isset($_GET['gateway'])) {
            wp_die(
                sprintf(__('Missing required parameter: %s', PayseraPaths::PAYSERA_TRANSLATIONS), 'gateway'),
                __('Invalid Request', PayseraPaths::PAYSERA_TRANSLATIONS),
                ['response' => 400]
            );
        }

        $gateway = sanitize_text_field(wp_unslash($_GET['gateway']));

        if ($gateway === '') {
            wp_die(
                __('Invalid gateway parameter', PayseraPaths::PAYSERA_TRANSLATIONS),
                __('Invalid Request', PayseraPaths::PAYSERA_TRANSLATIONS),
                ['response' => 400]
            );
        }

        return $gateway;
    }
}

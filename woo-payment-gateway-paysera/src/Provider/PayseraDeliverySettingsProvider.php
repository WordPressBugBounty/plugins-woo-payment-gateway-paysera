<?php

declare(strict_types=1);

namespace Paysera\Provider;

defined('ABSPATH') || exit;

use Paysera\Entity\PayseraDeliveryGatewaySettings;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Helper\LogHelper;
use PayseraWoocommerce;

class PayseraDeliverySettingsProvider
{
    public function getPayseraDeliveryGatewaySettings(string $deliveryGatewayCode, int $instanceId): PayseraDeliveryGatewaySettings
    {
        $className = ucwords(str_replace('_delivery', '', $deliveryGatewayCode), '_') . '_Delivery';
        $shippingMethod = new $className($instanceId);
        $minimumWeight = $shippingMethod->get_instance_option(PayseraDeliverySettings::MINIMUM_WEIGHT, PayseraDeliverySettings::DEFAULT_MINIMUM_WEIGHT);
        $maximumWeight = $shippingMethod->get_instance_option(PayseraDeliverySettings::MAXIMUM_WEIGHT, PayseraDeliverySettings::DEFAULT_MAXIMUM_WEIGHT);
        $senderType = $shippingMethod->get_instance_option(PayseraDeliverySettings::SENDER_TYPE, PayseraDeliverySettings::DEFAULT_TYPE);
        $receiverType = $shippingMethod->receiverType;

        return (new PayseraDeliveryGatewaySettings())
            ->setMinimumWeight((int) $minimumWeight)
            ->setMaximumWeight((int) $maximumWeight)
            ->setSenderType($senderType)
            ->setReceiverType($receiverType)
        ;
    }

    public function getPayseraDeliverySettings(): PayseraDeliverySettings
    {
        $settings = get_option(PayseraDeliverySettings::SETTINGS_NAME);
        $extraSettings = get_option(PayseraDeliverySettings::EXTRA_SETTINGS_NAME);
        $deliveryGatewaysSettings = get_option(PayseraDeliverySettings::DELIVERY_GATEWAYS_SETTINGS_NAME);
        $deliveryGatewaysTitles = get_option(PayseraDeliverySettings::DELIVERY_GATEWAYS_TITLES);
        $userAgent = $this->getDeliveryUserAgent();

        $payseraDeliverySettings = (new PayseraDeliverySettings())
            ->setGridViewEnabled(false)
            ->setHideShippingMethodsEnabled(true)
            ->setLogLevel(LogHelper::LOG_LEVEL_ERROR)
            ->setUserAgent($userAgent)
        ;

        if (isset($settings[PayseraDeliverySettings::ENABLED])) {
            $payseraDeliverySettings->setEnabled($settings[PayseraDeliverySettings::ENABLED] === 'yes');
        }

        if (isset($settings[PayseraDeliverySettings::PROJECT_ID])) {
            $projectId = trim($settings[PayseraDeliverySettings::PROJECT_ID]);
            $projectId = $projectId === '' ? null : (int) $projectId;
            $payseraDeliverySettings->setProjectId($projectId);
        }

        if (isset($deliveryGatewaysSettings[PayseraDeliverySettings::RESOLVED_PROJECT_ID])) {
            $payseraDeliverySettings->setResolvedProjectId(
                $deliveryGatewaysSettings[PayseraDeliverySettings::RESOLVED_PROJECT_ID]
            );
        }

        if (isset($settings[PayseraDeliverySettings::PROJECT_PASSWORD])) {
            $payseraDeliverySettings->setProjectPassword(trim($settings[PayseraDeliverySettings::PROJECT_PASSWORD]));
        }

        if (isset($settings[PayseraDeliverySettings::TEST_MODE])) {
            $payseraDeliverySettings->setTestModeEnabled(
                $settings[PayseraDeliverySettings::TEST_MODE]
                === 'yes'
            );
        }

        if (isset($settings[PayseraDeliverySettings::HOUSE_NUMBER_FIELD])) {
            $payseraDeliverySettings->setHouseNumberFieldEnabled(
                $settings[PayseraDeliverySettings::HOUSE_NUMBER_FIELD]
                === 'yes'
            );
        }

        if (isset($extraSettings[PayseraDeliverySettings::GRID_VIEW])) {
            $payseraDeliverySettings->setGridViewEnabled(
                $extraSettings[PayseraDeliverySettings::GRID_VIEW]
                === 'yes'
            );
        }

        if (isset($extraSettings[PayseraDeliverySettings::HIDE_SHIPPING_METHODS])) {
            $payseraDeliverySettings->setHideShippingMethodsEnabled(
                $extraSettings[PayseraDeliverySettings::HIDE_SHIPPING_METHODS]
                === 'yes'
            );
        }

        if (isset($deliveryGatewaysSettings[PayseraDeliverySettings::DELIVERY_GATEWAYS])) {
            $payseraDeliverySettings->setDeliveryGateways(
                $deliveryGatewaysSettings[PayseraDeliverySettings::DELIVERY_GATEWAYS]
            );
        }

        if (isset($deliveryGatewaysSettings[PayseraDeliverySettings::DELIVERY_GATEWAYS])) {
            $payseraDeliverySettings->setDeliveryGatewayTitles(
                $deliveryGatewaysTitles[PayseraDeliverySettings::DELIVERY_GATEWAYS]
            );
        }

        if (isset($deliveryGatewaysSettings[PayseraDeliverySettings::SHIPMENT_METHODS])) {
            $payseraDeliverySettings->setShipmentMethods(
                $deliveryGatewaysSettings[PayseraDeliverySettings::SHIPMENT_METHODS]
            );
        }

        if (isset($extraSettings[PayseraDeliverySettings::LOG_LEVEL])) {
            $payseraDeliverySettings->setLogLevel(
                $extraSettings[PayseraDeliverySettings::LOG_LEVEL]
            );
        }

        return $payseraDeliverySettings;
    }

    public function getActivePayseraDeliveryGateways(): array
    {
        $deliveryGatewaysSettings = get_option(PayseraDeliverySettings::DELIVERY_GATEWAYS_SETTINGS_NAME);
        $deliveryGateways = $deliveryGatewaysSettings[PayseraDeliverySettings::DELIVERY_GATEWAYS] ?? [];

        $activeDeliveryGateways = array_filter($deliveryGateways, function ($status) {
            return $status;
        });

        return array_keys($activeDeliveryGateways);
    }

    public function getDeliveryUserAgent(): string
    {
        return sprintf(
            'WordPress %s / WooCommerce %s / Paysera Plugin %s',
            get_bloginfo('version'),
            defined('WC_VERSION') ? WC_VERSION : 'unknown',
            PayseraWoocommerce::PAYSERA_PLUGIN_VERSION
        );
    }
}

<?php

declare(strict_types=1);

namespace Paysera\Entity;

use Paysera\DeliveryApi\MerchantClient\MerchantClient;
use WC_Order;

class PayseraDeliveryOrderRequest
{
    private WC_Order $order;
    private PayseraDeliverySettings $deliverySettings;
    private string $deliveryGatewayCode;
    private int $deliveryGatewayInstanceId;
    private MerchantClient $merchantClient;

    public function __construct(
        WC_Order $order,
        PayseraDeliverySettings $deliverySettings,
        string $deliveryGatewayCode,
        int $deliveryGatewayInstanceId,
        MerchantClient $merchantClient
    ) {
        $this->order = $order;
        $this->deliverySettings = $deliverySettings;
        $this->deliveryGatewayCode = $deliveryGatewayCode;
        $this->deliveryGatewayInstanceId = $deliveryGatewayInstanceId;
        $this->merchantClient = $merchantClient;
    }

    public function getOrder(): WC_Order
    {
        return $this->order;
    }

    public function getDeliverySettings(): PayseraDeliverySettings
    {
        return $this->deliverySettings;
    }

    public function getDeliveryGatewayCode(): string
    {
        return $this->deliveryGatewayCode;
    }

    public function getDeliveryGatewayInstanceId(): int
    {
        return $this->deliveryGatewayInstanceId;
    }

    public function getMerchantClient(): MerchantClient
    {
        return $this->merchantClient;
    }
}

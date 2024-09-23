<?php

declare(strict_types=1);

namespace Paysera\Service;

use Paysera\DeliveryApi\MerchantClient\Entity\Order;
use Paysera\Entity\PayseraDeliveryOrderRequest;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\Helper\PayseraDeliveryOrderRequestHelper;

class PayseraDeliveryOrderService
{
    private const ACTION_CREATE = 'create';
    private const ACTION_UPDATE = 'update';
    private const LOG_MESSAGE_STARTED = 'Attempting to perform operation \'%s\' of delivery order for order id %s with project id: %s';
    private const LOG_MESSAGE_COMPLETED = 'Operation \'%s\' of delivery order %s for order id %d is completed.';
    private const NOTES_MAP = [
        self::ACTION_CREATE => 'Delivery order created - %s',
        self::ACTION_UPDATE => 'Delivery order updated - %s',
    ];

    private PayseraDeliveryOrderRequestHelper $requestHelper;

    private LoggerInterface $logger;

    public function __construct(
        PayseraDeliveryOrderRequestHelper $requestHelper,
        LoggerInterface $logger
    ) {
        $this->requestHelper = $requestHelper;
        $this->logger = $logger;
    }

    public function createDeliveryOrder(PayseraDeliveryOrderRequest $deliveryOrderRequest): void
    {
        $this->logStepStarted(self::ACTION_CREATE, $deliveryOrderRequest);
        $deliveryOrder = $this->handleCreating($deliveryOrderRequest);

        if ($deliveryOrder === null) {
            return;
        }

        $this->logStepCompleted(self::ACTION_CREATE, $deliveryOrderRequest, $deliveryOrder);
    }

    public function updateDeliveryOrder(PayseraDeliveryOrderRequest $deliveryOrderRequest): void
    {
        $this->logStepStarted(self::ACTION_UPDATE, $deliveryOrderRequest);
        $deliveryOrder = $this->handleUpdating($deliveryOrderRequest);

        if ($deliveryOrder === null) {
            return;
        }

        $this->logStepCompleted(self::ACTION_UPDATE, $deliveryOrderRequest, $deliveryOrder);
    }

    #region Handling

    private function handleCreating(PayseraDeliveryOrderRequest $deliveryOrderRequest): ?Order
    {
        $order = $deliveryOrderRequest->getOrder();
        $deliveryOrder = $this->requestHelper->sendOrderCreateRequest($deliveryOrderRequest);

        if (!$deliveryOrder) {
            return null;
        }

        $order->update_meta_data(PayseraDeliverySettings::DELIVERY_ORDER_ID_META_KEY, $deliveryOrder->getId());
        $order->update_meta_data(PayseraDeliverySettings::DELIVERY_ORDER_NUMBER_META_KEY, $deliveryOrder->getNumber());
        $order->save_meta_data();

        return $deliveryOrder;
    }

    private function handleUpdating(PayseraDeliveryOrderRequest $deliveryOrderRequest): ?Order
    {
        return $this->requestHelper->sendOrderUpdateRequest(
            $deliveryOrderRequest->getOrder()->get_meta(PayseraDeliverySettings::DELIVERY_ORDER_ID_META_KEY),
            $deliveryOrderRequest
        );
    }

    #endregion

    #region Service Methods

    private function logStepStarted(string $action, PayseraDeliveryOrderRequest $request): void
    {
        $orderId = $request->getOrder()->get_id();
        $this->logger->info(
            sprintf(
                self::LOG_MESSAGE_STARTED,
                $action,
                $orderId,
                $request->getDeliverySettings()->getProjectId()
            )
        );
    }

    private function logStepCompleted(
        string $action,
        PayseraDeliveryOrderRequest $deliveryOrderRequest,
        Order $deliveryOrder
    ): void {
        $order = $deliveryOrderRequest->getOrder();
        $orderNumber = $deliveryOrder->getNumber();

        $this->logger->info(
            sprintf(
                self::LOG_MESSAGE_COMPLETED,
                $action,
                $orderNumber,
                $order->get_id(),
            )
        );

        $noteTemplate = self::NOTES_MAP[$action] ?? null;

        if ($noteTemplate !== null) {
            $order->add_order_note(
                sprintf(
                    __(PayseraPaths::PAYSERA_MESSAGE . $noteTemplate, PayseraPaths::PAYSERA_TRANSLATIONS),
                    $orderNumber,
                )
            );
        }
    }

    #endregion
}

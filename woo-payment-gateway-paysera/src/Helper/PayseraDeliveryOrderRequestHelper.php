<?php

declare(strict_types=1);

namespace Paysera\Helper;

use Exception;
use Paysera\DeliveryApi\MerchantClient\Entity\Order;
use Paysera\Entity\PayseraDeliveryOrderRequest;
use Paysera\Entity\PayseraPaths;
use Paysera\Service\LoggerInterface;
use WC_Order;

class PayseraDeliveryOrderRequestHelper
{
    private const ACTION_CREATE = 'create';
    private const ACTION_UPDATE = 'update';
    private const NOTES_MAP = [
        self::ACTION_CREATE => 'Delivery order creation failed, please create order manually in Paysera system',
        self::ACTION_UPDATE => 'Delivery order updating failed, please update order manually in Paysera system',
    ];

    private PayseraDeliveryOrderHelper $deliveryOrderHelper;
    private LoggerInterface $logger;

    public function __construct(
        PayseraDeliveryOrderHelper $deliveryOrderHelper,
        LoggerInterface $logger
    ) {
        $this->deliveryOrderHelper = $deliveryOrderHelper;
        $this->logger = $logger;
    }

    public function sendOrderCreateRequest(
        PayseraDeliveryOrderRequest $deliveryOrderRequest
    ): ?Order {
        $order = $deliveryOrderRequest->getOrder();

        try {
            return $deliveryOrderRequest
                ->getMerchantClient()
                ->createOrder(
                    $this->deliveryOrderHelper->getOrderCreate(
                        $order,
                        $deliveryOrderRequest->getDeliveryGatewayCode(),
                        $deliveryOrderRequest->getDeliveryGatewayInstanceId(),
                        $deliveryOrderRequest->getDeliverySettings()->getResolvedProjectId()
                    )
                )
            ;
        } catch (Exception $exception) {
            $this->handleException(self::ACTION_CREATE, $exception, $order);
        }

        return null;
    }

    public function sendOrderUpdateRequest(
        string $deliveryOrderId,
        PayseraDeliveryOrderRequest $deliveryOrderRequest
    ): ?Order {
        $order = $deliveryOrderRequest->getOrder();

        try {
            return $deliveryOrderRequest
                ->getMerchantClient()
                ->updateOrder(
                    $deliveryOrderId,
                    $this->deliveryOrderHelper->getOrderUpdate(
                        $order,
                        $deliveryOrderRequest->getDeliveryGatewayCode(),
                        $deliveryOrderRequest->getDeliveryGatewayInstanceId(),
                        $deliveryOrderRequest->getDeliverySettings()->getResolvedProjectId()
                    )
                )
            ;
        } catch (Exception $exception) {
            $this->handleException(self::ACTION_UPDATE, $exception, $order);
        }

        return null;
    }

    private function handleException(string $action, Exception $exception, WC_Order $order): void
    {
        $this->logger->error(
            sprintf(
                'Cannot perform operation \'%s\' on delivery order for order id %s.',
                $action,
                $order->get_id(),
            ),
            $exception
        );

        $note = self::NOTES_MAP[$action] ?? null;

        if ($note !== null) {
            $this->noteException(
                $order,
                $exception,
                __(PayseraPaths::PAYSERA_MESSAGE . $note, PayseraPaths::PAYSERA_TRANSLATIONS)
            );
        }
    }

    private function noteException(WC_Order $order, Exception $exception, string $errorDescription): void
    {
        $errorMessage = method_exists($exception, 'getResponse')
            ? $exception->getResponse()->getBody()->getContents()
            : $exception->getMessage();

        $order->add_order_note(
            sprintf(
                '%s<br>%s<br>%s',
                $errorDescription,
                __('Error:', PayseraPaths::PAYSERA_TRANSLATIONS),
                $errorMessage
            )
        );
    }
}

<?php

declare(strict_types=1);

namespace Paysera\Service;

use Exception;
use Paysera\Scoped\Paysera\DeliverySdk\DeliveryFacade;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliveryOrderRequest;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\DeliveryGatewayNotFoundException;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\DeliveryOrderRequestException;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\MerchantClientNotFoundException;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\UndefinedDeliveryGatewayException;
use Paysera\Exception\DeliveryActionException;
use Paysera\Utils\OrderNotesFormatter;

class PayseraDeliveryOrderService
{
    private DeliveryFacade $deliverySdk;
    private OrderNotesFormatter $orderNotesFormatter;

    public function __construct(
        DeliveryFacade $deliverySdk,
        OrderNotesFormatter $orderNotesFormatter
    ) {
        $this->deliverySdk = $deliverySdk;
        $this->orderNotesFormatter = $orderNotesFormatter;
    }

    public function setDeliveryOrderStatusPrepaid(
        PayseraDeliveryOrderRequest $deliveryOrderRequest
    ): void {
        $this->deliverySdk->prepaidDeliveryOrder($deliveryOrderRequest);
    }

    /**
     * @param PayseraDeliveryOrderRequest $deliveryOrderRequest
     * @return void
     * @throws DeliveryActionException
     * @throws DeliveryOrderRequestException
     * @throws MerchantClientNotFoundException
     */
    public function createDeliveryOrder(PayseraDeliveryOrderRequest $deliveryOrderRequest): void
    {
        try {
            $order = $this->deliverySdk->createDeliveryOrder($deliveryOrderRequest);

            $order->getWcOrder()->add_order_note($this->orderNotesFormatter->formatActionCreateNote($order));
        } catch (DeliveryOrderRequestException|MerchantClientNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new DeliveryActionException('creation', $e);
        }
    }

    /**
     * @param PayseraDeliveryOrderRequest $deliveryOrderRequest
     * @return void
     * @throws DeliveryActionException
     * @throws DeliveryOrderRequestException
     * @throws MerchantClientNotFoundException
     */
    public function updateDeliveryOrder(PayseraDeliveryOrderRequest $deliveryOrderRequest): void
    {
        try {
            $order = $this->deliverySdk->updateDeliveryOrder($deliveryOrderRequest);

            $order->getWcOrder()->add_order_note($this->orderNotesFormatter->formatActionUpdateNote($order));
        } catch (DeliveryOrderRequestException|MerchantClientNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new DeliveryActionException('updating', $e);
        }
    }
}

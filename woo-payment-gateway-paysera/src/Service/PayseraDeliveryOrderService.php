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
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Exception\DeliveryActionException;
use Paysera\Provider\PayseraRatesProvider;
use Paysera\Utils\OrderNotesFormatter;

class PayseraDeliveryOrderService
{
    private DeliveryFacade $deliverySdk;
    private OrderNotesFormatter $orderNotesFormatter;
    private PayseraRatesProvider $ratesProvider;

    public function __construct(
        DeliveryFacade $deliverySdk,
        OrderNotesFormatter $orderNotesFormatter,
        PayseraRatesProvider $ratesProvider
    ) {
        $this->deliverySdk = $deliverySdk;
        $this->orderNotesFormatter = $orderNotesFormatter;
        $this->ratesProvider = $ratesProvider;
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
        $this->convertDefaultParcelToBaseUnits($deliveryOrderRequest);

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
        $this->convertDefaultParcelToBaseUnits($deliveryOrderRequest);

        try {
            $order = $this->deliverySdk->updateDeliveryOrder($deliveryOrderRequest);

            $order->getWcOrder()->add_order_note($this->orderNotesFormatter->formatActionUpdateNote($order));
        } catch (DeliveryOrderRequestException|MerchantClientNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new DeliveryActionException('updating', $e);
        }
    }

    private function convertDefaultParcelToBaseUnits(PayseraDeliveryOrderRequest $deliveryOrderRequest): void
    {
        $deliverySettings = $deliveryOrderRequest->getDeliverySettings();

        if (!$deliverySettings instanceof PayseraDeliverySettings) {
            return;
        }

        $weightRate = (int)$this->ratesProvider->getRateByKey((string)get_option('woocommerce_weight_unit'));
        $dimensionRate = (int)$this->ratesProvider->getRateByKey((string)get_option('woocommerce_dimension_unit'));

        $deliverySettings
            ->setDefaultParcelWidth($deliverySettings->getDefaultParcelWidth() * $dimensionRate)
            ->setDefaultParcelHeight($deliverySettings->getDefaultParcelHeight() * $dimensionRate)
            ->setDefaultParcelLength($deliverySettings->getDefaultParcelLength() * $dimensionRate)
            ->setDefaultParcelWeight($deliverySettings->getDefaultParcelWeight() * $weightRate)
        ;
    }
}

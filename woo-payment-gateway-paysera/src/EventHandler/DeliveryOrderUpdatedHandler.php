<?php

declare(strict_types=1);

namespace Paysera\EventHandler;

use Paysera\Scoped\Paysera\DeliverySdk\DeliveryFacade;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliveryOrderRequest;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\DeliveryGatewayNotFoundException;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\InvalidTypeException;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\MerchantClientNotFoundException;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\UndefinedDeliveryGatewayException;
use Paysera\Scoped\Paysera\DeliverySdk\Service\DeliveryLoggerInterface;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Repository\MerchantOrderRepository;
use Paysera\Utils\OrderNotesFormatter;
use Throwable;
use WC_Order;

class DeliveryOrderUpdatedHandler implements EventHandlerInterface
{
    private DeliveryLoggerInterface $logger;
    private DeliveryFacade $deliverySdk;
    private MerchantOrderRepository $merchantOrderRepository;
    private PayseraDeliverySettingsProvider $deliverySettingsProvider;
    private OrderNotesFormatter $orderNotesFormatter;

    public function __construct(
        DeliveryFacade $deliverySdk,
        MerchantOrderRepository $merchantOrderRepository,
        PayseraDeliverySettingsProvider $deliverySettingsProvider,
        OrderNotesFormatter $orderNotesFormatter,
        DeliveryLoggerInterface $logger
    ) {
        $this->deliverySdk = $deliverySdk;
        $this->merchantOrderRepository = $merchantOrderRepository;
        $this->deliverySettingsProvider = $deliverySettingsProvider;
        $this->orderNotesFormatter = $orderNotesFormatter;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     * @throws InvalidTypeException
     */
    public function handle(array $payload): void
    {
        $merchantOrder = $this->merchantOrderRepository->findOrderById((int)($payload['orderId'] ?? 0));

        if ($merchantOrder === null || $merchantOrder->getDeliveryOrderId() === null) {
            return;
        }

        $deliveryOrderRequest = new PayseraDeliveryOrderRequest(
            $merchantOrder,
            $this->deliverySettingsProvider->getPayseraDeliverySettings()
        );

        try {
            $this->deliverySdk->updateMerchantOrder($deliveryOrderRequest);
        } catch (MerchantClientNotFoundException $e) {
            return;
        } catch (UndefinedDeliveryGatewayException $e) {
            $this->logger->error('Can\'t define delivery gateway code from delivery order', $e);
        } catch (DeliveryGatewayNotFoundException $e) {
            $this->logger->error($e->getMessage());

            $merchantOrder
                ->getWcOrder()
                ->add_order_note($this->orderNotesFormatter->formatUndefinedDeliveryGatewayNote($merchantOrder))
            ;
        } catch (Throwable $e) {
            $this->logger->error('Delivery order updates handling failed', $e);
        }
    }
}

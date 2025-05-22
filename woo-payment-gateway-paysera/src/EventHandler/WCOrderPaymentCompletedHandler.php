<?php

declare(strict_types=1);

namespace Paysera\EventHandler;

use Paysera\Factory\OrderFactory;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Repository\MerchantOrderRepository;
use Paysera\Scoped\Paysera\DeliverySdk\DeliveryFacade;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\DeliveryGatewayNotFoundException;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\InvalidTypeException;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\MerchantClientNotFoundException;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\UndefinedDeliveryGatewayException;
use Paysera\Scoped\Paysera\DeliverySdk\Service\DeliveryLoggerInterface;
use Paysera\Service\PayseraDeliveryOrderService;
use Paysera\Utils\OrderNotesFormatter;
use Throwable;
use WC_Order;

class WCOrderPaymentCompletedHandler implements EventHandlerInterface
{
    private DeliveryLoggerInterface $logger;
    private PayseraDeliveryOrderService $deliveryOrderService;
    private MerchantOrderRepository $merchantOrderRepository;
    private PayseraDeliverySettingsProvider $deliverySettingsProvider;
    private OrderNotesFormatter $orderNotesFormatter;
    private OrderFactory $orderFactory;

    public function __construct(
        PayseraDeliveryOrderService $deliveryOrderService,
        MerchantOrderRepository $merchantOrderRepository,
        PayseraDeliverySettingsProvider $deliverySettingsProvider,
        OrderNotesFormatter $orderNotesFormatter,
        DeliveryLoggerInterface $logger,
        OrderFactory $orderFactory
    ) {
        $this->deliveryOrderService = $deliveryOrderService;
        $this->merchantOrderRepository = $merchantOrderRepository;
        $this->deliverySettingsProvider = $deliverySettingsProvider;
        $this->orderNotesFormatter = $orderNotesFormatter;
        $this->logger = $logger;
        $this->orderFactory = $orderFactory;
    }

    /**
     * @inheritDoc
     * @throws InvalidTypeException
     */
    public function handle(array $payload): void
    {
        /** @var WC_Order|null $order */
        $order = $payload['order'] ?? null;

        if (!$order instanceof WC_Order) {
            $this->logger->error('WCOrderPaymentCompletedHandler: Attempting to handle non WooCommerce order');

            return;
        }

        $merchantOrder = $this->orderFactory->createFromWcOrder($order);

        if ($merchantOrder === null || $merchantOrder->getDeliveryOrderId() === null) {
            return;
        }

        $deliveryOrderRequest = new \Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliveryOrderRequest(
            $merchantOrder,
            $this->deliverySettingsProvider->getPayseraDeliverySettings()
        );

        try {
            if (empty($merchantOrder->getDeliveryOrderId()) === false) {
                $this->deliveryOrderService->setDeliveryOrderStatusPrepaid($deliveryOrderRequest);
            }
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
            $this->logger->error('Delivery order prepaid handling failed', $e);
        }
    }
}

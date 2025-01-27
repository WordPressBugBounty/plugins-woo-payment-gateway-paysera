<?php

declare(strict_types=1);

namespace Paysera\EventHandler;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\DeliveryTerminalLocationFactoryInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliveryOrderRequest;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliverySettingsInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\DeliveryOrderRequestException;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\InvalidTypeException;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\MerchantClientNotFoundException;
use Paysera\Scoped\Paysera\DeliverySdk\Service\DeliveryLoggerInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Util\DeliveryGatewayUtils;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\Exception\DeliveryActionException;
use Paysera\Factory\OrderFactory;
use Paysera\Helper\SessionHelperInterface;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Service\PayseraDeliveryOrderService;
use Paysera\Utils\LogMessageFormatter;
use Paysera\Utils\OrderNotesFormatter;
use WC_Order;

class WCOrderCreatedHandler implements EventHandlerInterface
{
    private PayseraDeliveryOrderService $deliveryOrderService;
    private PayseraDeliverySettingsProvider $deliverySettingsProvider;
    private SessionHelperInterface $sessionHelper;
    private OrderFactory $orderFactory;
    private OrderNotesFormatter $orderNotesFormatter;
    private LogMessageFormatter $logMessageFormatter;
    private DeliveryGatewayUtils $deliveryGatewayUtils;
    private DeliveryTerminalLocationFactoryInterface $terminalLocationFactory;
    private DeliveryLoggerInterface $logger;

    private ?string $deliveryGatewayCode;
    private ?int $deliveryGatewayInstanceId;

    public function __construct(
        PayseraDeliveryOrderService $deliveryOrderService,
        PayseraDeliverySettingsProvider $deliverySettingsProvider,
        SessionHelperInterface $sessionHelper,
        OrderFactory $orderFactory,
        OrderNotesFormatter $orderNotesFormatter,
        LogMessageFormatter $logMessageFormatter,
        DeliveryGatewayUtils $deliveryGatewayUtils,
        DeliveryTerminalLocationFactoryInterface $terminalLocationFactory,
        DeliveryLoggerInterface $logger
    ) {
        $this->deliveryOrderService = $deliveryOrderService;
        $this->deliverySettingsProvider = $deliverySettingsProvider;
        $this->sessionHelper = $sessionHelper;
        $this->orderFactory = $orderFactory;
        $this->orderNotesFormatter = $orderNotesFormatter;
        $this->logMessageFormatter = $logMessageFormatter;
        $this->deliveryGatewayUtils = $deliveryGatewayUtils;
        $this->terminalLocationFactory = $terminalLocationFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     * @throws InvalidTypeException
     * @throws DeliveryOrderRequestException
     */
    public function handle(array $payload): void
    {
        /** @var WC_Order|null $order */
        $order = $payload['order'] ?? null;

        if (!$order instanceof WC_Order) {
            $this->logger->error('Attempting to handle non WooCommerce order');

            return;
        }

        $orderId = $order->get_id();

        if (!$this->sessionHelper->isSessionAvailable()) {
            $this->logger->info(sprintf('WooCommerce session is not available for order id %d.', $orderId));

            return;
        }

        $deliverySettings = $this->deliverySettingsProvider->getPayseraDeliverySettings();

        if ($deliverySettings->isTestModeEnabled() === true) {
            $this->logger->info(sprintf('Test mode is enabled for order id %d.', $orderId));

            return;
        }

        $merchantOrder = $this->orderFactory->createFromWcOrder($order);
        $deliveryGateway = $merchantOrder->getDeliveryGateway();

        if ($deliveryGateway === null) {
            $this->logger->info(sprintf('Paysera delivery gateway code not found for order id %d.', $orderId));

            return;
        }

        $this->logger->info(
            sprintf(
                'Delivery gateway code for order id %d: %s.',
                $orderId,
                $deliveryGateway->getCode()
            )
        );

        $deliveryOrderRequest = new PayseraDeliveryOrderRequest($merchantOrder, $deliverySettings);

        $this->initHouseNumber($deliveryOrderRequest);
        $this->initSelectedTerminal($deliveryOrderRequest);
        $this->logOrderItems($deliveryOrderRequest);

        try {
            if ($order->meta_exists(PayseraDeliverySettings::DELIVERY_ORDER_ID_META_KEY)) {
                $this->deliveryOrderService->updateDeliveryOrder($deliveryOrderRequest);
            } else {
                $this->deliveryOrderService->createDeliveryOrder($deliveryOrderRequest);
            }
        } catch (MerchantClientNotFoundException $e) {
            $this->logger->error(sprintf('Delivery merchant client not found for order id %d.', $orderId));
        } catch (DeliveryOrderRequestException $e) {
            $this->logger->error(sprintf('Delivery order request failed for order %d.', $orderId), $e);
        } catch (DeliveryActionException $e) {
            $this->handleException($e, $order);
        }
    }

    private function initHouseNumber(PayseraDeliveryOrderRequest $deliveryOrderRequest)
    {
        $shippingAddress = $deliveryOrderRequest->getOrder()->getShipping()->getAddress();

        if ($this->sessionHelper->getData(PayseraDeliverySettings::SHIPPING_HOUSE_NO) !== '') {
            $shippingAddress->setHouseNumber(
                $this->sessionHelper->getData(PayseraDeliverySettings::SHIPPING_HOUSE_NO)
            );
        } elseif ($this->sessionHelper->getData(PayseraDeliverySettings::BILLING_HOUSE_NO) !== '') {
            $shippingAddress->setHouseNumber(
                $this->sessionHelper->getData(PayseraDeliverySettings::BILLING_HOUSE_NO)
            );
        }
    }

    private function initSelectedTerminal(PayseraDeliveryOrderRequest $deliveryOrderRequest): void
    {
        $deliveryGateway = $deliveryOrderRequest->getOrder()->getDeliveryGateway();

        if (
            $deliveryGateway->getSettings()->getReceiverType() === PayseraDeliverySettingsInterface::TYPE_PARCEL_MACHINE
        ) {
            $selectedTerminal = $this->terminalLocationFactory->create()
                ->setCountry($this->sessionHelper->getData(PayseraDeliverySettings::TERMINAL_COUNTRY))
                ->setCity($this->sessionHelper->getData(PayseraDeliverySettings::TERMINAL_CITY))
                ->setDeliveryGatewayCode(
                    $this->deliveryGatewayUtils->resolveDeliveryGatewayCode($deliveryGateway->getCode())
                )
                ->setTerminalId($this->sessionHelper->getData(PayseraDeliverySettings::TERMINAL))
            ;

            $deliveryOrderRequest
                ->getOrder()
                ->getShipping()
                ->setTerminalLocation($selectedTerminal)
            ;

            $this->logger->info(
                $this->logMessageFormatter->formatSelectedTerminal(
                    $deliveryOrderRequest->getOrder(),
                    $selectedTerminal,
                )
            );

            $deliveryOrderRequest
                ->getOrder()
                ->getWcOrder()
                ->add_order_note(
                    $this->orderNotesFormatter->formatSelectedTerminalNote(
                        $selectedTerminal,
                        $deliveryOrderRequest->getDeliverySettings(),
                    )
                )
            ;
        }
    }

    private function logOrderItems(PayseraDeliveryOrderRequest $order): void
    {
        foreach ($order->getOrder()->getItems() as $item) {
            $this->logger->info(
                $this->logMessageFormatter->formatOrderItem($order->getOrder(), $item)
            );
        }
    }

    private function handleException(DeliveryActionException $exception, WC_Order $order): void
    {
        $this->logger->error(
            sprintf(
                'Cannot perform operation \'%s\' on delivery order for order id %s.',
                $exception->getAction(),
                $order->get_id(),
            ),
            $exception
        );

        $this->noteException(
            $order,
            $exception,
            __(PayseraPaths::PAYSERA_MESSAGE . $exception->getMessage(), PayseraPaths::PAYSERA_TRANSLATIONS)
        );
    }

    private function noteException(WC_Order $order, DeliveryActionException $exception, string $errorDescription): void
    {
        $exception = $exception->getPrevious();

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

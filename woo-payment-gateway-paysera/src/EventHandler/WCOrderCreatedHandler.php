<?php

declare(strict_types=1);

namespace Paysera\EventHandler;

use Paysera\Entity\PayseraDeliveryOrderRequest;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Helper\PayseraDeliveryHelper;
use Paysera\Helper\PayseraDeliveryLibraryHelper;
use Paysera\Helper\SessionHelperInterface;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Service\LoggerInterface;
use Paysera\Service\PayseraDeliveryOrderService;
use WC_Order;

class WCOrderCreatedHandler implements EventHandlerInterface
{
    private PayseraDeliveryOrderService $deliveryOrderService;
    private PayseraDeliveryLibraryHelper $deliveryLibraryHelper;
    private PayseraDeliverySettingsProvider $deliverySettingsProvider;
    private PayseraDeliveryHelper $deliveryHelper;
    private SessionHelperInterface $sessionHelper;
    private LoggerInterface $logger;

    public function __construct(
        PayseraDeliveryOrderService $deliveryOrderService,
        PayseraDeliveryLibraryHelper $deliveryLibraryHelper,
        PayseraDeliverySettingsProvider $deliverySettingsProvider,
        PayseraDeliveryHelper $deliveryHelper,
        SessionHelperInterface $sessionHelper,
        LoggerInterface $logger
    ) {
        $this->deliveryOrderService = $deliveryOrderService;
        $this->deliveryLibraryHelper = $deliveryLibraryHelper;
        $this->deliverySettingsProvider = $deliverySettingsProvider;
        $this->deliveryHelper = $deliveryHelper;
        $this->sessionHelper = $sessionHelper;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
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

        $deliveryGatewayCode = null;
        $deliveryGatewayInstanceId = null;

        foreach ($order->get_shipping_methods() as $shippingMethod) {
            if ($this->deliveryHelper->isPayseraDeliveryGateway($shippingMethod->get_method_id())) {
                $deliveryGatewayCode = $shippingMethod->get_method_id();
                $deliveryGatewayInstanceId = (int)$shippingMethod->get_instance_id();

                $this->logger->info(sprintf(
                    'Delivery gateway code for order id %d: %s.',
                    $orderId,
                    $deliveryGatewayCode
                ));

                break;
            }
        }

        if ($deliveryGatewayCode === null) {
            $this->logger->info(sprintf('Paysera delivery gateway code not found for order id %d.', $orderId));

            return;
        }

        $merchantClient = $this->deliveryLibraryHelper->getMerchantClient();

        if ($merchantClient === null) {
            $this->logger->error(sprintf('Delivery merchant client not found for order id %d.', $orderId));

            return;
        }

        $deliveryOrderRequest = new PayseraDeliveryOrderRequest(
            $order,
            $deliverySettings,
            $deliveryGatewayCode,
            $deliveryGatewayInstanceId,
            $merchantClient,
        );

        if ($order->meta_exists(PayseraDeliverySettings::DELIVERY_ORDER_ID_META_KEY)) {
            $this->deliveryOrderService->updateDeliveryOrder($deliveryOrderRequest);
        } else {
            $this->deliveryOrderService->createDeliveryOrder($deliveryOrderRequest);
        }
    }
}
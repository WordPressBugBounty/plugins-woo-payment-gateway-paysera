<?php

declare(strict_types=1);

namespace Paysera\Service;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\DeliveryTerminalLocationInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliveryGatewayInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Service\DeliveryLoggerInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Service\MerchantOrderLoggerInterface;
use Paysera\Entity\Delivery\DeliveryTerminalLocation;
use Paysera\Entity\Delivery\Order;
use Paysera\Entity\PayseraPaths;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Utils\LogMessageFormatter;
use Paysera\Utils\OrderNotesFormatter;

class MerchantOrderLogger implements MerchantOrderLoggerInterface
{
    private DeliveryLoggerInterface $deliveryLogger;
    private OrderNotesFormatter $orderNotesFormatter;
    private LogMessageFormatter $logMessageFormatter;
    private PayseraDeliverySettingsProvider $deliverySettingsProvider;

    public function __construct(
        DeliveryLoggerInterface $deliveryLogger,
        OrderNotesFormatter $orderNotesFormatter,
        LogMessageFormatter $logMessageFormatter,
        PayseraDeliverySettingsProvider $deliverySettingsProvider
    ) {
        $this->deliveryLogger = $deliveryLogger;
        $this->orderNotesFormatter = $orderNotesFormatter;
        $this->logMessageFormatter = $logMessageFormatter;
        $this->deliverySettingsProvider = $deliverySettingsProvider;
    }

    /**
     * @param Order $merchantOrder
     * @param array $oldData
     * @param array $newData
     * @return void
     */
    public function logShippingChanges(MerchantOrderInterface $merchantOrder, array $oldData, array $newData): void
    {
        $this->deliveryLogger->info(
            $this->logMessageFormatter->formatShippingChanges($merchantOrder, $oldData, $newData)
        );

        $merchantOrder->getWcOrder()->add_order_note(
            __(
                PayseraPaths::PAYSERA_MESSAGE . 'Receiver\'s data has been changed',
                PayseraPaths::PAYSERA_TRANSLATIONS
            )
        );
    }

    /**
     * @param MerchantOrderInterface $merchantOrder
     * @param PayseraDeliveryGatewayInterface $oldGateway
     * @param PayseraDeliveryGatewayInterface $newGateway
     * @return void
     */
    public function logDeliveryGatewayChanges(
        MerchantOrderInterface $merchantOrder,
        PayseraDeliveryGatewayInterface $oldGateway,
        PayseraDeliveryGatewayInterface $newGateway
    ): void {
        $merchantOrder->getWcOrder()->add_order_note(
            $this->orderNotesFormatter->formatChangedDeliveryGatewayNote($newGateway)
        );

        $this->deliveryLogger->info(
            $this->logMessageFormatter->formatChangedDeliveryGateway($merchantOrder, $oldGateway, $newGateway)
        );
    }

    /**
     * @param Order $merchantOrder
     * @param DeliveryTerminalLocation|null $oldTerminalLocation
     * @param DeliveryTerminalLocation $newTerminalLocation
     * @return void
     */
    public function logDeliveryTerminalLocationChanges(
        MerchantOrderInterface $merchantOrder,
        ?DeliveryTerminalLocationInterface $oldTerminalLocation,
        DeliveryTerminalLocationInterface $newTerminalLocation
    ): void {
        $deliverySettings = $this->deliverySettingsProvider->getPayseraDeliverySettings();

        $merchantOrder->getWcOrder()->add_order_note(
            $this->orderNotesFormatter->formatChangedTerminalNote($newTerminalLocation, $deliverySettings)
        );

        $this->deliveryLogger->info(
            $this->logMessageFormatter->formatChangedTerminal(
                $deliverySettings,
                $newTerminalLocation,
                $oldTerminalLocation
            )
        );
    }
}

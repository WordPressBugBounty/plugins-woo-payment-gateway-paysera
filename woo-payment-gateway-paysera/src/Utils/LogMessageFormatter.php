<?php

declare(strict_types=1);

namespace Paysera\Utils;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\DeliveryTerminalLocationInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderItemInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliveryGatewayInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliverySettingsInterface;
use Paysera\Dto\TerminalLocationMessageDto;
use Paysera\Entity\PayseraPaths;
use Paysera\Helper\TerminalsHelper;

class LogMessageFormatter
{
    private const ORDER_ITEM_DIMENSIONS = 'Shipment product dimensions for order id %d: Weight: %s, Length: %s, Width: %s, Height: %s.';
    private const SHIPPING_CHANGES = 'Receiver data changed for order id %d.';
    private const PREVIOUS_SHIPPING_CHANGES = 'Previous: %s';
    private const CURRENT_SHIPPING_CHANGES = 'Current: %s';
    private const SHIPPING_DATA_TEMPLATES = [
        'shipping.contact.email' => 'Billing email: %s',
        'shipping.contact.phone' => 'Shipping phone: %s',
        'shipping.address.street' => 'Street %s',
        'shipping.address.postalCode' => 'Postal code: %s',
        'shipping.address.country' => 'Country: %s',
        'shipping.address.city' => 'City: %s',
        'shipping.address.houseNumber' => 'House number: %s',
    ];
    private const TERMINAL_LOCATION_SELECTED = 'Terminal session data for order id %d. Country: %s, City: %s, Terminal: %s';
    private const TERMINAL_LOCATION_CHANGED = 'Terminal location has been changed';
    private const PREVIOUS_TERMINAL = 'Previous: %s, %s, %s';
    private const CURRENT_TERMINAL = 'Current: %s, %s, %s';
    private const DELIVERY_GATEWAY_CHANGED = 'Delivery gateway for order %d has been changed from %s(%s) to %s(%s).';

    private TerminalsHelper $terminalsHelper;

    public function __construct(TerminalsHelper $terminalsHelper)
    {
        $this->terminalsHelper = $terminalsHelper;
    }

    public function formatOrderItem(MerchantOrderInterface $order, MerchantOrderItemInterface $orderItem): string
    {
        $msg = sprintf(
            self::ORDER_ITEM_DIMENSIONS,
            $order->getNumber(),
            $orderItem->getWeight(),
            $orderItem->getLength(),
            $orderItem->getWidth(),
            $orderItem->getHeight()
        );

        return __($msg, PayseraPaths::PAYSERA_TRANSLATIONS);
    }

    #region Shipping Changes

    public function formatShippingChanges(MerchantOrderInterface $order, array $oldData, array $newData): string
    {
        $msg = implode(
            PHP_EOL,
            [
                sprintf(self::SHIPPING_CHANGES, $order->getNumber()),
                $this->prepareShippingDataInfoString($oldData, self::PREVIOUS_SHIPPING_CHANGES),
                $this->prepareShippingDataInfoString($newData, self::CURRENT_SHIPPING_CHANGES),
            ]
        );

        return __($msg, PayseraPaths::PAYSERA_TRANSLATIONS);
    }

    private function prepareShippingDataInfoString(array $recipientData, string $msgTemplate): string
    {
        $newline = "\n\t";
        $messageData = [];

        foreach ($recipientData as $field => $value) {
            $messageData[] = isset(self::SHIPPING_DATA_TEMPLATES[$field])
                ? sprintf(self::SHIPPING_DATA_TEMPLATES[$field], (string)$value)
                : sprintf('%s:%s', $field, $value);
        }

        return sprintf($msgTemplate,$newline . implode($newline, $messageData));
    }

    #endregion

    public function formatChangedDeliveryGateway(
        MerchantOrderInterface $merchantOrder,
        PayseraDeliveryGatewayInterface $oldGateway,
        PayseraDeliveryGatewayInterface $newGateway
    ): string {
        $msg = sprintf(
            self::DELIVERY_GATEWAY_CHANGED,
            $merchantOrder->getNumber(),
            $oldGateway->getName(),
            $oldGateway->getCode(),
            $newGateway->getName(),
            $newGateway->getCode(),
        );

        return __($msg, PayseraPaths::PAYSERA_TRANSLATIONS);
    }

    #region Terminals

    public function formatSelectedTerminal(
        MerchantOrderInterface $order,
        DeliveryTerminalLocationInterface $terminalLocation
    ): string {
        $countries = WC()->countries->get_countries();

        $msg = sprintf(
            self::TERMINAL_LOCATION_SELECTED,
            $order->getNumber(),
            $countries[$terminalLocation->getCountry()] ?? $terminalLocation->getCountry(),
            $terminalLocation->getCity(),
            $terminalLocation->getTerminalId(),
        );

        return __($msg, PayseraPaths::PAYSERA_TRANSLATIONS);
    }

    public function formatChangedTerminal(
        PayseraDeliverySettingsInterface $deliverySettings,
        DeliveryTerminalLocationInterface $newTerminalLocation,
        ?DeliveryTerminalLocationInterface $oldTerminalLocation = null
    ): string {
        $countries = WC()->countries->get_countries();

        $oldTerminalLocationMsg = $oldTerminalLocation ?
            $this->formatTerminalMessage(
                new TerminalLocationMessageDto(
                    $deliverySettings,
                    $oldTerminalLocation,
                    $countries,
                    self::PREVIOUS_TERMINAL
                )
            )
            : null;

        $msg = implode(
            PHP_EOL,
            array_filter(
                [
                    PayseraPaths::PAYSERA_MESSAGE .  self::TERMINAL_LOCATION_CHANGED . ':',
                    $oldTerminalLocationMsg,
                    $this->formatTerminalMessage(
                        new TerminalLocationMessageDto(
                            $deliverySettings,
                            $newTerminalLocation,
                            $countries,
                            self::CURRENT_TERMINAL
                        )
                    ),
                ]
            )
        );

        return __($msg, PayseraPaths::PAYSERA_TRANSLATIONS);
    }

    private function formatTerminalMessage(TerminalLocationMessageDto $messageDto): string
    {
        $terminal = $messageDto->getTerminalLocation();
        $terminals = $this->terminalsHelper->getTerminalsLocations($terminal, $messageDto->getDeliverySettings());

        return sprintf(
            $messageDto->getMsgTemplate(),
            $messageDto->getTerminalCountry(),
            $terminal->getCity(),
            $terminals[$terminal->getTerminalId()]
        );
    }

    #endregion
}

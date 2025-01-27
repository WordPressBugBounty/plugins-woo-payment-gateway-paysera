<?php

declare(strict_types=1);

namespace Paysera\Utils;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\DeliveryTerminalLocationInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliveryGatewayInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliverySettingsInterface;
use Paysera\Entity\PayseraPaths;
use Paysera\Helper\TerminalsHelper;

class OrderNotesFormatter
{
    private const ACTION_CREATE = 'Delivery order created - %s';
    private const ACTION_UPDATE = 'Delivery order updated - %s';
    private const CHOSEN_TERMINAL_LOCATION = 'Chosen terminal location - %s, %s, %s';
    private const TERMINAL_LOCATION_CHANGED = 'Terminal location has been changed - %s, %s, %s';
    private const UNDEFINED_GATEWAY = 'Shipping method of delivery order %s has been changed, but it doesn\'t match with any active shipping method.';
    private const DELIVERY_GATEWAY_CHANGED = 'Shipping method has been changed to %s';

    private TerminalsHelper $terminalHelper;

    public function __construct(TerminalsHelper $terminalHelper)
    {
        $this->terminalHelper = $terminalHelper;
    }

    public function formatUndefinedDeliveryGatewayNote(MerchantOrderInterface $merchantOrder): string
    {
        return sprintf(
            __(PayseraPaths::PAYSERA_MESSAGE . self::UNDEFINED_GATEWAY, PayseraPaths::PAYSERA_TRANSLATIONS),
            $merchantOrder->getDeliveryOrderNumber(),
        );
    }

    public function formatChangedDeliveryGatewayNote(PayseraDeliveryGatewayInterface $deliveryGateway): string
    {
        return sprintf(
            __(
                PayseraPaths::PAYSERA_MESSAGE . self::DELIVERY_GATEWAY_CHANGED,
                PayseraPaths::PAYSERA_TRANSLATIONS
            ),
            $deliveryGateway->getName()
        );
    }

    #region Actions

    public function formatActionCreateNote(MerchantOrderInterface $order): string
    {
        return $this->formatActionNote($order, self::ACTION_CREATE);
    }

    public function formatActionUpdateNote(MerchantOrderInterface $order): string
    {
        return $this->formatActionNote($order, self::ACTION_UPDATE);
    }

    private function formatActionNote(MerchantOrderInterface $order, string $noteTemplate): string
    {
        return sprintf(
            __(PayseraPaths::PAYSERA_MESSAGE . $noteTemplate, PayseraPaths::PAYSERA_TRANSLATIONS),
            $order->getDeliveryOrderNumber(),
        );
    }

    #endregion

    #region Terminals

    public function formatSelectedTerminalNote(
        DeliveryTerminalLocationInterface $selectedTerminalLocation,
        PayseraDeliverySettingsInterface $deliverySettings
    ): string {
        return $this->formatTerminalNote(
            $selectedTerminalLocation,
            $deliverySettings,
            self::CHOSEN_TERMINAL_LOCATION
        );
    }
    public function formatChangedTerminalNote(
        DeliveryTerminalLocationInterface $selectedTerminalLocation,
        PayseraDeliverySettingsInterface $deliverySettings
    ): string {
        return $this->formatTerminalNote(
            $selectedTerminalLocation,
            $deliverySettings,
            self::TERMINAL_LOCATION_CHANGED
        );
    }

    private function formatTerminalNote(
        DeliveryTerminalLocationInterface $selectedTerminalLocation,
        PayseraDeliverySettingsInterface $deliverySettings,
        string $noteTemplate
    ): string {
        $countryName = WC()->countries->get_countries()[$selectedTerminalLocation->getCountry()];
        $terminals = $this->terminalHelper->getTerminalsLocations($selectedTerminalLocation, $deliverySettings);

        return sprintf(
            __(
                PayseraPaths::PAYSERA_MESSAGE . $noteTemplate,
                PayseraPaths::PAYSERA_TRANSLATIONS
            ),
            $countryName,
            $selectedTerminalLocation->getCity(),
            $terminals[$selectedTerminalLocation->getTerminalId()]
        );
    }

    #endregion
}

<?php

declare(strict_types=1);

namespace Paysera\Dto;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\DeliveryTerminalLocationInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliverySettingsInterface;

class TerminalLocationMessageDto
{
    private PayseraDeliverySettingsInterface $deliverySettings;
    private DeliveryTerminalLocationInterface $terminalLocation;
    private array $countries;
    private string $msgTemplate;

    public function __construct(
        PayseraDeliverySettingsInterface $deliverySettings,
        DeliveryTerminalLocationInterface $terminalLocation,
        array $countries,
        string $msgTemplate
    ) {
        $this->deliverySettings = $deliverySettings;
        $this->terminalLocation = $terminalLocation;
        $this->countries = $countries;
        $this->msgTemplate = $msgTemplate;
    }

    public function getDeliverySettings(): PayseraDeliverySettingsInterface
    {
        return $this->deliverySettings;
    }

    public function getTerminalLocation(): DeliveryTerminalLocationInterface
    {
        return $this->terminalLocation;
    }

    public function getTerminalCountry(): string
    {
        return $this->countries[$this->terminalLocation->getCountry()];
    }

    public function getMsgTemplate(): string
    {
        return $this->msgTemplate;
    }
}

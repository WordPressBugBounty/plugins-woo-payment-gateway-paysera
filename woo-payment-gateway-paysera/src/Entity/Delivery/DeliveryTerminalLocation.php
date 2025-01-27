<?php

declare(strict_types=1);

namespace Paysera\Entity\Delivery;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\DeliveryTerminalLocationInterface;

class DeliveryTerminalLocation extends AbstractEntity implements DeliveryTerminalLocationInterface
{
    private string $countryCode;
    private string $city;
    private ?string $selectedTerminalId;
    private string $deliveryGatewayCode;

    public function setCountry(string $country): DeliveryTerminalLocationInterface
    {
        $this->countryCode = $country;

        return $this;
    }

    public function getCountry(): string
    {
        return $this->countryCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): DeliveryTerminalLocationInterface
    {
        $this->city = $city;

        return $this;
    }

    public function getTerminalId(): string
    {
        return $this->selectedTerminalId;
    }

    public function setTerminalId(string $terminalId): DeliveryTerminalLocationInterface
    {
        $this->selectedTerminalId = $terminalId;

        return $this;
    }

    public function getDeliveryGatewayCode(): string
    {
        return $this->deliveryGatewayCode;
    }

    public function setDeliveryGatewayCode(string $gatewayCode): DeliveryTerminalLocationInterface
    {
        $this->deliveryGatewayCode = $gatewayCode;

        return $this;
    }
}

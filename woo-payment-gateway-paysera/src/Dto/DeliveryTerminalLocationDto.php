<?php

declare(strict_types=1);

namespace Paysera\Dto;

class DeliveryTerminalLocationDto
{
    private string $countryCode;
    private string $city;
    private ?string $selectedTerminalId;
    private string $deliveryGatewayCode;

    public function __construct(
        string $country,
        string $city,
        string $deliveryGatewayCode,
        string $selectedTerminalId = null
    ) {
        $this->countryCode = $country;
        $this->city = $city;
        $this->deliveryGatewayCode = $deliveryGatewayCode;
        $this->selectedTerminalId = $selectedTerminalId;
    }

    public function getCountryCode(): string
    {
        return $this->countryCode;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getSelectedTerminalId(): string
    {
        return $this->selectedTerminalId;
    }

    public function getDeliveryGatewayCode(): string
    {
        return $this->deliveryGatewayCode;
    }
}

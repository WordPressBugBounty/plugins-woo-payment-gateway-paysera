<?php

declare(strict_types=1);

namespace Paysera\Helper;

use Paysera\DeliveryApi\MerchantClient\Entity\ParcelMachineFilter;
use Paysera\Scoped\Paysera\DeliverySdk\Client\Provider\MerchantClientProvider;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\DeliveryTerminalLocationInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\PayseraDeliverySettingsInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\MerchantClientNotFoundException;
use Paysera\Entity\Delivery\DeliveryTerminalLocation;
use Paysera\Entity\PayseraDeliverySettings;

class TerminalsHelper
{
    private MerchantClientProvider $merchantClientProvider;

    public function __construct(
        MerchantClientProvider $merchantClientProvider
    ) {
        $this->merchantClientProvider = $merchantClientProvider;
    }

    /**
     * @param DeliveryTerminalLocation $terminalLocationDto
     * @param PayseraDeliverySettings $deliverySettings
     * @return array
     * @throws MerchantClientNotFoundException
     */
    public function getTerminalsLocations(
        DeliveryTerminalLocationInterface $terminalLocationDto,
        PayseraDeliverySettingsInterface $deliverySettings
    ): array {
        $parcelMachineFilter = (new ParcelMachineFilter())
            ->setCountry($terminalLocationDto->getCountry())
            ->setCity($terminalLocationDto->getCity())
            ->setShipmentGatewayCode($terminalLocationDto->getDeliveryGatewayCode());

        $locations = [];

        $merchantClient = $this->merchantClientProvider->getMerchantClient($deliverySettings);
        $parcelMachines = $merchantClient->getParcelMachines($parcelMachineFilter)->getList();

        foreach ($parcelMachines as $parcelMachine) {
            $locationInfo = [];

            $locationInfo[] = $parcelMachine->getAddress()->getStreet() ?? '';
            $locationInfo[] = $parcelMachine->getLocationName() ?? '';

            $locations[$parcelMachine->getId()] = implode(', ', array_filter($locationInfo));
        }

        asort($locations);

        return $locations;
    }
}

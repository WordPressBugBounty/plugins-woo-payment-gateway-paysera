<?php

declare(strict_types=1);

namespace Paysera\Helper;

defined('ABSPATH') || exit;

use Exception;
use Paysera\Action\PayseraDeliveryActions;
use Paysera\DeliveryApi\MerchantClient\Entity\CityFilter;
use Paysera\DeliveryApi\MerchantClient\Entity\CountryFilter;
use Paysera\DeliveryApi\MerchantClient\Entity\GatewaysFilter;
use Paysera\DeliveryApi\MerchantClient\Entity\MethodsFilter;
use Paysera\DeliveryApi\MerchantClient\Entity\ParcelMachineFilter;
use Paysera\DeliveryApi\MerchantClient\Entity\ShipmentGateway;
use Paysera\DeliveryApi\MerchantClient\Entity\ShipmentMethod;
use Paysera\DeliveryApi\MerchantClient\MerchantClient;
use Paysera\Scoped\Paysera\DeliverySdk\Client\Provider\MerchantClientProvider;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\DeliveryTerminalLocationInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\MerchantClientNotFoundException;
use Paysera\Scoped\Paysera\DeliverySdk\Service\DeliveryLoggerInterface;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use WC_Product;

class PayseraDeliveryLibraryHelper
{
    private PayseraDeliverySettings $deliverySettings;
    private MerchantClientProvider $merchantClientProvider;
    private DeliveryLoggerInterface $logger;

    public function __construct(
        MerchantClientProvider $merchantClientProvider,
        PayseraDeliverySettingsProvider $deliverySettingsProvider,
        DeliveryLoggerInterface $logger
    ) {
        $this->merchantClientProvider = $merchantClientProvider;
        $this->deliverySettings = $deliverySettingsProvider->getPayseraDeliverySettings();
        $this->logger = $logger;
    }

    /**
     * @return ShipmentGateway[]
     */
    public function getPayseraDeliveryGateways(): array
    {
        $gatewaysFilter = (new GatewaysFilter());

        $resolvedProjectId = $this->deliverySettings->getResolvedProjectId();

        if ($resolvedProjectId !== null) {
            $gatewaysFilter->setProjectId($resolvedProjectId);
        }

        $merchantClient = $this->getMerchantClient();

        if ($merchantClient === null) {
            return [];
        }

        try {
            $deliveryGateways = $merchantClient->updateGateway($gatewaysFilter)->getList();
        } catch (Exception $exception) {
            $this->logger->error('Could not fetch delivery gateways.', $exception);

            return [];
        }

        return $deliveryGateways;
    }

    /**
     * @return ShipmentMethod[]
     */
    public function getPayseraShipmentMethods(): array
    {
        $methodsFilter = (new MethodsFilter());

        $resolvedProjectId = $this->deliverySettings->getResolvedProjectId();

        if ($resolvedProjectId !== null) {
            $methodsFilter->setProjectId($resolvedProjectId);
        }

        $merchantClient = $this->getMerchantClient();

        if ($merchantClient === null) {
            return [];
        }

        try {
            $shipmentMethods = $merchantClient->updateMethod($methodsFilter)->getList();
        } catch (Exception $exception) {
            $this->logger->error('Could not fetch shipment methods.', $exception);

            return [];
        }

        return $shipmentMethods;
    }

    public function getParcelMachinesLocations(DeliveryTerminalLocationInterface $terminalLocationDto): array
    {
        $parcelMachineFilter = (new ParcelMachineFilter())
            ->setCountry($terminalLocationDto->getCountry())
            ->setCity($terminalLocationDto->getCity())
            ->setShipmentGatewayCode($terminalLocationDto->getDeliveryGatewayCode())
        ;

        $merchantClient = $this->getMerchantClient();

        if ($merchantClient === null) {
            return [];
        }

        try {
            $parcelMachines = $merchantClient->getParcelMachines($parcelMachineFilter)->getList();
        } catch (Exception $exception) {
            $this->logger->error('Could not fetch parcel machines.', $exception);

            return [];
        }

        $locations = [];

        foreach ($parcelMachines as $parcelMachine) {
            $locationInfo = [];

            $locationInfo[] = $parcelMachine->getAddress()->getStreet() ?? '';
            $locationInfo[] = $parcelMachine->getLocationName() ?? '';

            $locations[$parcelMachine->getId()] = implode(', ', array_filter($locationInfo));
        }

        asort($locations);

        return $locations;
    }

    public function getPayseraCountries(string $deliveryGatewayCode): array
    {
        $countryFilter = (new CountryFilter())->setShipmentGatewayCode($deliveryGatewayCode);

        $merchantClient = $this->getMerchantClient();

        if ($merchantClient === null) {
            return [];
        }

        try {
            $countries = $merchantClient->getCountries($countryFilter)->getItems();
        } catch (Exception $exception) {
            $this->logger->error('Could not fetch countries.', $exception);

            return [];
        }

        $normalizedCountries = [];

        foreach ($countries as $country) {
            $countryCode = $country->getCountryCode();
            $normalizedCountries[$countryCode] = WC()->countries->get_countries()[$countryCode];
        }

        asort($normalizedCountries);

        return $normalizedCountries;
    }

    public function getPayseraCities(string $country, string $deliveryGatewayCode): array
    {
        $cityFilter = (new CityFilter())
            ->setCountry($country)
            ->setGatewayCode($deliveryGatewayCode)
        ;

        $merchantClient = $this->getMerchantClient();

        if ($merchantClient === null) {
            return [];
        }

        try {
            $cities = $merchantClient->getCities($cityFilter)->getItems();
        } catch (Exception $exception) {
            $this->logger->error('Could not fetch cities.', $exception);

            return [];
        }

        $normalizedCities = [];

        foreach ($cities as $city) {
            $normalizedCities[] = $city->getName();
        }

        asort($normalizedCities);

        return $normalizedCities;
    }

    public function getShippingOptionLogoUrls(): array
    {
        $logos = [];

        foreach ($this->getPayseraDeliveryGateways() as $deliveryGateway) {
            if (!$deliveryGateway->isEnabled()) {
                continue;
            }

            $data = [
                'url' => $deliveryGateway->getLogo(),
                'name' => $deliveryGateway->getDescription(),
            ];

            $logos[$deliveryGateway->getCode() . '_courier'] = $data;

            if (!in_array($deliveryGateway->getCode(), PayseraDeliverySettings::PARCEL_MACHINE_DISABLED_DELIVERY_GATEWAYS)) {
                $logos[$deliveryGateway->getCode() . '_terminals'] = $data;
            }
        }

        return $logos;
    }

    private function getMerchantClient(): ?MerchantClient
    {
        try {
            return $this->merchantClientProvider->getMerchantClient(
                $this->deliverySettings
            );
        } catch (MerchantClientNotFoundException $e) {
            return null;
        }
    }
}

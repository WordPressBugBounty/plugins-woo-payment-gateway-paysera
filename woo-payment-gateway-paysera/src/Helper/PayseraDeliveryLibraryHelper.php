<?php

declare(strict_types=1);

namespace Paysera\Helper;

defined('ABSPATH') || exit;

use Exception;
use Paysera\Action\PayseraDeliveryActions;
use Paysera\DeliveryApi\MerchantClient\Entity\CityFilter;
use Paysera\DeliveryApi\MerchantClient\Entity\Contact;
use Paysera\DeliveryApi\MerchantClient\Entity\CountryFilter;
use Paysera\DeliveryApi\MerchantClient\Entity\GatewaysFilter;
use Paysera\DeliveryApi\MerchantClient\Entity\MethodsFilter;
use Paysera\DeliveryApi\MerchantClient\Entity\ParcelMachine;
use Paysera\DeliveryApi\MerchantClient\Entity\ParcelMachineFilter;
use Paysera\DeliveryApi\MerchantClient\Entity\ShipmentCreate;
use Paysera\DeliveryApi\MerchantClient\Entity\ShipmentGateway;
use Paysera\DeliveryApi\MerchantClient\Entity\ShipmentMethod;
use Paysera\DeliveryApi\MerchantClient\Entity\ShipmentPointCreate;
use Paysera\DeliveryApi\MerchantClient\MerchantClient;
use Paysera\Dto\DeliveryTerminalLocationDto;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\Provider\MerchantClientProvider;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use Paysera\Provider\PayseraRatesProvider;
use Paysera\Service\LoggerInterface;
use WC_Product;

class PayseraDeliveryLibraryHelper
{
    private const NOTE_CHOSEN_TERMINAL_LOCATION = 'Chosen terminal location - %s, %s, %s';
    private const NOTE_TERMINAL_LOCATION_CHANGED = self::MSG_TERMINAL_LOCATION_CHANGED . ' - %s, %s, %s';
    private const MSG_TERMINAL_LOCATION_CHANGED = 'Terminal location has been changed';
    private const PREVIOUS_TERMINAL_LOG_MSG = 'Previous: %s, %s, %s';
    private const CURRENT_TERMINAL_LOG_MSG = 'Current: %s, %s, %s';

    private PayseraDeliverySettings $payseraDeliverySettings;
    private PayseraDeliveryActions $payseraDeliveryActions;
    private PayseraRatesProvider $payseraRatesProvider;
    private MerchantClientProvider $merchantClientProvider;
    private LoggerInterface $logger;

    public function __construct(
        PayseraDeliveryActions $payseraDeliveryActions,
        MerchantClientProvider $merchantClientProvider,
        LoggerInterface $logger
    ) {
        $this->payseraDeliveryActions = $payseraDeliveryActions;
        $this->merchantClientProvider = $merchantClientProvider;
        $this->payseraRatesProvider = new PayseraRatesProvider();
        $this->payseraDeliverySettings = (new PayseraDeliverySettingsProvider())->getPayseraDeliverySettings();
        $this->logger = $logger;
    }

    /**
     * @return ShipmentGateway[]
     */
    public function getPayseraDeliveryGateways(): array
    {
        $gatewaysFilter = (new GatewaysFilter());

        $resolvedProjectId = $this->payseraDeliverySettings->getResolvedProjectId();

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

        $resolvedProjectId = $this->payseraDeliverySettings->getResolvedProjectId();

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

    public function getParcelMachinesLocations(DeliveryTerminalLocationDto $terminalLocationDto): array
    {
        $parcelMachineFilter = (new ParcelMachineFilter())
            ->setCountry($terminalLocationDto->getCountryCode())
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

    public function getMerchantClient(): ?MerchantClient
    {
        return $this->merchantClientProvider->getMerchantClient(
            $this->payseraDeliverySettings->getProjectId(),
            $this->payseraDeliverySettings->getProjectPassword()
        );
    }

    public function createOrderParty(
        string $orderPartyMethod,
        string $type,
        ?Contact $contact,
        string $parcelMachineId = null
    ): ShipmentPointCreate {
        $orderParty = (new ShipmentPointCreate())
            ->setType($type)
            ->setSaved(false)
            ->setDefaultContact(false)
        ;

        if ($this->payseraDeliverySettings->getResolvedProjectId() !== null) {
            $orderParty->setProjectId($this->payseraDeliverySettings->getResolvedProjectId());
        }

        if ($contact !== null) {
            $orderParty->setContact($contact);
        }

        if (
            ($orderPartyMethod === PayseraDeliverySettings::TYPE_PARCEL_MACHINE)
            && $type === 'receiver'
            && $parcelMachineId !== null
        ) {
            $orderParty->setParcelMachineId($parcelMachineId);
        }

        return $orderParty;
    }

    public function createShipment(WC_Product $product): ShipmentCreate
    {
        $weightRate = $this->payseraRatesProvider->getRateByKey(get_option('woocommerce_weight_unit'));
        $dimensionRate = $this->payseraRatesProvider->getRateByKey(get_option('woocommerce_dimension_unit'));

        $weight = !empty($product->get_weight()) ? $product->get_weight() : '0';
        $length = !empty($product->get_length()) ? $product->get_length() : '0';
        $width = !empty($product->get_width()) ? $product->get_width() : '0';
        $height = !empty($product->get_height()) ? $product->get_height() : '0';

        return (new ShipmentCreate())
            ->setWeight((int) ($weight * $weightRate))
            ->setLength((int) ($length * $dimensionRate))
            ->setWidth((int) ($width * $dimensionRate))
            ->setHeight((int) ($height * $dimensionRate))
        ;
    }

    public function formatSelectedTerminalNote(DeliveryTerminalLocationDto $selectedTerminalLocation): string
    {
        return $this->formatTerminalNote($selectedTerminalLocation, self::NOTE_CHOSEN_TERMINAL_LOCATION);
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

    public function getPayseraDeliveryActions(): PayseraDeliveryActions
    {
        return $this->payseraDeliveryActions;
    }

    public function formatChangedTerminalNote(DeliveryTerminalLocationDto $selectedTerminalLocation): string
    {
        return $this->formatTerminalNote($selectedTerminalLocation, self::NOTE_TERMINAL_LOCATION_CHANGED);
    }


    public function formatChangedTerminalLogMsg(
        DeliveryTerminalLocationDto $newTerminalLocation,
        ?DeliveryTerminalLocationDto $oldTerminalLocation = null
    ): string {
        $countries = WC()->countries->get_countries();
        $msg = implode(
            "\n",
            array_filter(
                [
                    PayseraPaths::PAYSERA_MESSAGE .  self::MSG_TERMINAL_LOCATION_CHANGED . ':',
                    $this->formatPreviousTerminalLogMsg($oldTerminalLocation, $countries),
                    $this->formatCurrentTerminalLogMsg($newTerminalLocation, $countries),
                ]
            )
        );

        return __($msg, PayseraPaths::PAYSERA_TRANSLATIONS);
    }

    private function formatTerminalNote(
        DeliveryTerminalLocationDto $selectedTerminalLocation,
        string $noteTemplate
    ): string {
        $countryName = WC()->countries->get_countries()[$selectedTerminalLocation->getCountryCode()];
        $terminals = $this->getParcelMachinesLocations($selectedTerminalLocation);

        return sprintf(
            __(
                PayseraPaths::PAYSERA_MESSAGE . $noteTemplate,
                PayseraPaths::PAYSERA_TRANSLATIONS
            ),
            $countryName,
            $selectedTerminalLocation->getCity(),
            $terminals[$selectedTerminalLocation->getSelectedTerminalId()]
        );
    }

    private function formatPreviousTerminalLogMsg(
        ?DeliveryTerminalLocationDto $oldTerminalLocation,
        array $countries
    ): ?string {
        return $oldTerminalLocation === null
            ? null
            : $this->formatTerminalLogMsg($oldTerminalLocation, $countries, self::PREVIOUS_TERMINAL_LOG_MSG);
    }

    private function formatCurrentTerminalLogMsg(
        DeliveryTerminalLocationDto $newTerminalLocation,
        array $countries
    ): string {
        return $this->formatTerminalLogMsg($newTerminalLocation, $countries, self::CURRENT_TERMINAL_LOG_MSG);
    }

    private function formatTerminalLogMsg(
        DeliveryTerminalLocationDto $terminalLocation,
        array $countries,
        string $msgTemplate
    ): string {
        $terminals = $this->getParcelMachinesLocations($terminalLocation);

        return sprintf(
            $msgTemplate,
            $countries[$terminalLocation->getCountryCode()],
            $terminalLocation->getCity(),
            $terminals[$terminalLocation->getSelectedTerminalId()]
        );
    }
}

<?php

declare(strict_types=1);

namespace Paysera\Helper;

defined('ABSPATH') || exit;

use Paysera\DeliveryApi\MerchantClient\Entity\Address;
use Paysera\Scoped\Paysera\DeliverySdk\Entity\DeliveryTerminalLocationFactoryInterface;
use Paysera\Scoped\Paysera\DeliverySdk\Util\DeliveryGatewayUtils;
use Paysera\Entity\PayseraDeliverySettings;
use Paysera\Entity\PayseraPaths;
use Paysera\PayseraInit;
use Paysera\Provider\PayseraDeliverySettingsProvider;
use WC;
use WC_Order;
use WC_Order_Item_Shipping;
use WC_Shipping_Zone;
use WC_Shipping_Zones;

class PayseraDeliveryHelper
{
    private PayseraDeliveryLibraryHelper $payseraDeliveryLibraryHelper;
    private SessionHelperInterface $sessionHelper;
    private ?float $cartTotalWeight = null;
    private DeliveryTerminalLocationFactoryInterface $terminalLocationFactory;
    private DeliveryGatewayUtils $deliveryGatewayUtils;

    public function __construct(
        PayseraDeliveryLibraryHelper $payseraDeliveryLibraryHelper,
        SessionHelperInterface $sessionHelper,
        DeliveryTerminalLocationFactoryInterface $terminalLocationFactory,
        DeliveryGatewayUtils $deliveryGatewayUtils
    ) {
        $this->payseraDeliveryLibraryHelper = $payseraDeliveryLibraryHelper;
        $this->sessionHelper = $sessionHelper;
        $this->terminalLocationFactory = $terminalLocationFactory;
        $this->deliveryGatewayUtils = $deliveryGatewayUtils;
    }

    public function settingsUrl(array $query = []): string
    {
        return esc_url(admin_url('admin.php?page=paysera-delivery') . '&' . http_build_query($query));
    }

    public function resolveDeliveryGatewayCode(string $deliveryGatewayCode): string
    {
        $lastDelimPosition = strripos($deliveryGatewayCode, ':');

        return str_replace(
            ['_terminals', '_courier', PayseraDeliverySettings::DELIVERY_GATEWAY_PREFIX],
            '',
            $lastDelimPosition
                ? substr($deliveryGatewayCode, 0, $lastDelimPosition)
                : $deliveryGatewayCode
        );
    }

    /**
     * Extracts code and instance id from selected shipping method in old WooCommerce
     * @param string $chosenMethod
     * @return array
     */
    public function extractDeliveryGatewayDataFromShippingMethod(string $chosenMethod): array
    {
        $method = explode(':', $chosenMethod);
        $deliveryGatewayCode = $method[0];
        $deliveryGatewayInstanceId = null;

        if (isset($method[1])) {
            $deliveryGatewayInstanceId = (int)$method[1];
        }

        return [
            'code' => $deliveryGatewayCode,
            'instanceId' => $deliveryGatewayInstanceId,
        ];
    }

    public function isPayseraDeliveryGateway(string $deliveryGateway): bool
    {
        return (strpos($deliveryGateway, PayseraDeliverySettings::DELIVERY_GATEWAY_PREFIX) !== false);
    }

    public function deliveryGatewayClassExists(string $deliveryGateway, string $gatewayType): bool
    {
        return file_exists(
            plugin_dir_path(__FILE__) . 'Entity/class-paysera-' . $deliveryGateway . '-' . $gatewayType
                . '-delivery.php'
        );
    }

    public function getAvailableCountriesByShippingMethodId(string $shippingMethodId): array
    {
        foreach (WC_Shipping_Zones::get_zones() as $shippingZone) {
            foreach ($shippingZone['shipping_methods'] as $shippingMethod) {
                if ($shippingMethod->id === $shippingMethodId) {
                    return $this->formatShippingZoneCountries($shippingZone['zone_locations']);
                }
            }
        }

        return [];
    }

    private function formatShippingZoneCountries(array $shippingZoneLocations): array
    {
        $countryCodes = [];

        foreach ($shippingZoneLocations as $location) {
            $countryCodes[] = $location->code;
        }

        return $countryCodes;
    }

    public function getFormattedCountriesByShippingMethod(string $shippingMethod): array
    {
        $countries = [];
        $countries['default'] = __('Please select the country', PayseraPaths::PAYSERA_TRANSLATIONS);

        $shippingMethodCountries = $this->getAvailableCountriesByShippingMethodId(
            $shippingMethod
        );

        $payseraCountries = $this->payseraDeliveryLibraryHelper->getPayseraCountries(
            $this->resolveDeliveryGatewayCode($shippingMethod)
        );

        foreach ($payseraCountries as $countryCode => $payseraCountry) {
            if (empty($shippingMethodCountries) === true || in_array($countryCode, $shippingMethodCountries, true)) {
                $countries[$countryCode] = $payseraCountry;
            }
        }

        return $countries;
    }

    public function getFormattedCitiesByCountry(string $shippingMethod, string $shippingCountry): array
    {
        $cities = [];
        $cities['default'] = __('Please select the city/municipality', PayseraPaths::PAYSERA_TRANSLATIONS);

        foreach ($this->payseraDeliveryLibraryHelper->getPayseraCities(
            $shippingCountry,
            $this->resolveDeliveryGatewayCode($shippingMethod)
        ) as $city) {
            $cities[$city] = $city;
        }

        $this->sessionHelper->setData('paysera_terminal_country', $shippingCountry);

        return $cities;
    }

    public function getFormattedLocationsByCountryAndCity(string $shippingMethod, string $shippingCountry, string $shippingCity): array
    {
        $terminalLocations = [];
        $terminalLocations['default'] = __('Please select the terminal location', PayseraPaths::PAYSERA_TRANSLATIONS);

        $terminalLocations = array_merge(
            $terminalLocations,
            $this->payseraDeliveryLibraryHelper->getParcelMachinesLocations(
                $this->terminalLocationFactory->create()
                    ->setCountry($shippingCountry)
                    ->setCity($shippingCity)
                    ->setDeliveryGatewayCode(
                        $this->deliveryGatewayUtils->resolveDeliveryGatewayCode($shippingMethod)
                    )
            )
        );

        $this->sessionHelper->setData('paysera_terminal_city', $shippingCity);

        return $terminalLocations;
    }

    public function getShippingOptionLogoUrls(): array
    {
        return $this->payseraDeliveryLibraryHelper->getShippingOptionLogoUrls();
    }

    public function getPayseraDeliveryGateways(): array
    {
        return $this->payseraDeliveryLibraryHelper->getPayseraDeliveryGateways();
    }

    public function canApplyShippingZone(WC_Shipping_Zone $zone, Address $address): bool
    {
        $locations = $zone->get_zone_locations();
        $countries = array_column(array_filter($locations, fn ($location) => 'country' === $location->type), 'code');

        if (!empty($countries) && !in_array($address->getCountry(), $countries, true)) {
            return false;
        }

        $state = WC()->countries->get_states()[$address->getState()] ?? null;

        if ($state !== null) {
            $states = array_column(array_filter($locations, fn ($location) => 'state' === $location->type), 'code');

            if (!empty($states) && !in_array($state, $states, true)) {
                return false;
            }
        }

        $actualPostcode = $address->getPostalCode();
        $shippingZonePostcodes = array_filter($locations, fn ($location) => 'postcode' === $location->type);

        if ($actualPostcode !== null && !empty($shippingZonePostcodes)) {
            return !empty(
                wc_postcode_location_matcher(
                    $actualPostcode,
                    $shippingZonePostcodes,
                    'code',
                    'code',
                    $address->getCountry()
                )
            );
        }

        return true;
    }

    public function getCartTotalDeliveryWeight(): float
    {
        if ($this->cartTotalWeight === null) {
            $this->cartTotalWeight = 0;

            foreach (WC()->cart->cart_contents as $item) {
                $product = wc_get_product($item['product_id']);

                $this->cartTotalWeight += (float) ($product->get_weight() ?? 0) * (float) $item['quantity'];
            }

            if (get_option('woocommerce_weight_unit') === 'g') {
                $this->cartTotalWeight /= 1000;
            }
        }

        return $this->cartTotalWeight;
    }
}

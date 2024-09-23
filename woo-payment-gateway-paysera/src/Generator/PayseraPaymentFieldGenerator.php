<?php

declare(strict_types=1);

namespace Paysera\Generator;

defined('ABSPATH') || exit;

use Paysera\Front\PayseraPaymentFrontHtml;
use Paysera\Helper\PayseraPaymentLibraryHelper;
use Paysera\Entity\PayseraPaymentSettings;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\Collection\PaymentMethodCountryCollection;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\PaymentMethodGroup;
use Paysera\Scoped\Paysera\CheckoutSdk\Service\Translator;

class PayseraPaymentFieldGenerator
{
    /**
     * @var PayseraPaymentSettings
     */
    private $payseraPaymentSettings;
    private $payseraPaymentFrontHtml;
    private $payseraPaymentLibraryHelper;

    public function __construct(
        PayseraPaymentSettings $payseraPaymentSettings,
        PayseraPaymentFrontHtml $payseraPaymentFrontHtml,
        PayseraPaymentLibraryHelper $payseraPaymentLibraryHelper
    ) {
        $this->payseraPaymentSettings = $payseraPaymentSettings;
        $this->payseraPaymentFrontHtml = $payseraPaymentFrontHtml;
        $this->payseraPaymentLibraryHelper = $payseraPaymentLibraryHelper;
    }

    public function generatePaymentField(): string
    {
        $billingCountry = strtolower(WC()->customer->get_billing_country());

        $paymentField = '';

        if ($this->payseraPaymentSettings->isListOfPaymentsEnabled() === true) {
            $countries = $this->getCountriesWithMethodList();

            if (count($countries) > 1) {
                $paymentField .= $this->payseraPaymentFrontHtml->buildCountriesList($countries, $billingCountry)
                    . '<br/>'
                ;
            }

            $paymentField .= $this->payseraPaymentFrontHtml->buildPaymentsList(
                $countries,
                $this->payseraPaymentSettings->isGridViewEnabled(),
                $billingCountry
            );
        } else {
            $paymentField = $this->payseraPaymentSettings->getDescription() . '<br/>';
        }

        if ($this->payseraPaymentSettings->isBuyerConsentEnabled() === true) {
            $paymentField .= '<br/>' . $this->payseraPaymentFrontHtml->buildBuyerConsent();
        }

        return $paymentField;
    }

    public function getPaymentBlockCountries(): array
    {
        $translator = new Translator();
        $language = $this->payseraPaymentSettings->getLanguage();

        $countries = $this->getCountriesWithMethodList();
        $countryList = [];

        foreach ($countries as $country) {
            $groups = [];

            /** @var PaymentMethodGroup $group */
            foreach ($country['groups'] as $group) {
                $methods = [];

                foreach ($group->getPaymentMethods() as $paymentMethod) {
                    $methods[] = [
                        'key' => $paymentMethod->getKey(),
                        'title' => $translator->getTitle($paymentMethod, $language),
                        'logo' => $translator->getLogo($paymentMethod, $language),
                    ];
                }

                $groups[] = [
                    'title' => $translator->getTitle($group, $language),
                    'methods' => $methods,
                ];
            }

            $countryList[] = [
                'code' => $country['code'],
                'title' => $country['title'],
                'groups' => $groups,
            ];
        }

        return $countryList;
    }

    private function getCountriesWithMethodList(): array
    {
        return $this->getCountries(
            $this->payseraPaymentLibraryHelper->getPaymentMethodList(
                $this->payseraPaymentSettings->getProjectId(),
                (float)(WC()->cart->cart_contents_total),
                get_woocommerce_currency()
            )
        );
    }

    private function getCountries(?PaymentMethodCountryCollection $payseraCountries): array
    {
        if ($payseraCountries === null) {
            return [];
        }

        $translator = new Translator();
        $language = $this->payseraPaymentSettings->getLanguage();
        $specificCountries = $this->payseraPaymentSettings->getSpecificCountries();

        $countries = [];
        $otherCountry = null;

        foreach ($payseraCountries as $country) {
            if ($otherCountry === null && $country->getCode() === 'other') {
                $otherCountry = $country;
            }

            if (
                empty($specificCountries) === false
                && in_array(strtoupper($country->getCode()), $specificCountries, true) === false
            ) {
                continue;
            }

            $countries[] = [
                'code' => strtolower($country->getCode()),
                'title' => $translator->getTitle($country, $language),
                'groups' => $country->getGroups(),
            ];
        }

        foreach ($specificCountries as $specificCountry) {
            if (
                in_array(strtolower($specificCountry), array_column($countries, 'code'), true) === false
                && in_array('other', array_column($countries, 'code'), true) === false
            ) {
                if ($otherCountry !== null) {
                    $countries[] = [
                        'code' => strtolower($otherCountry->getCode()),
                        'title' => $translator->getTitle($otherCountry, $language),
                        'groups' => $otherCountry->getGroups(),
                    ];
                }
            }
        }

        return $countries;
    }
}

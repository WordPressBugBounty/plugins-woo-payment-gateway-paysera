<?php

declare(strict_types=1);

namespace Paysera\Front;

defined('ABSPATH') || exit;

use Paysera\Entity\PayseraPaymentSettings;
use Paysera\Entity\PayseraPaths;
use Paysera\Provider\PayseraPaymentSettingsProvider;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\Collection\PaymentMethodCollection;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\Collection\PaymentMethodGroupCollection;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\PaymentMethodGroup;
use Paysera\Scoped\Paysera\CheckoutSdk\Service\Translator;

class PayseraPaymentFrontHtml
{
    private PayseraPaymentSettings $payseraPaymentSettings;

    public function __construct()
    {
        $this->payseraPaymentSettings = (new PayseraPaymentSettingsProvider())->getPayseraPaymentSettings();
    }

    public function buildCountriesList(array $countries, string $billingCountryCode): string
    {
        $html = '<select id="paysera_country" class="payment-country-select" >';

        foreach ($countries as $country) {
            $isCountrySelected = $country['code'] === $this->getDefaultCountryCode($countries, $billingCountryCode);

            $html .= '<option value="' . $country['code'] . '" ' . (($isCountrySelected === true) ? 'selected' : '')
                . '>' . strtoupper($country['title']) . '</option>'
            ;
        }

        $html .= '</select>';

        return $html;
    }

    public function buildPaymentsList(array $countries, bool $isGridViewEnabled, string $billingCountryCode): string
    {
        $html = '';

        foreach ($countries as $country) {
            $isCountrySelected = $country['code'] === $this->getDefaultCountryCode($countries, $billingCountryCode);

            $html .= '<div id="' . $country['code'] . '" ' . ' class="payment-countries paysera-payments'
                . (($isGridViewEnabled) ? ' grid"' : '"') . ' style="display:'
                . (($isCountrySelected === true) ? 'block' : 'none') . '">'
                . $this->buildGroupList($country['groups'], $country['code'], $isCountrySelected) . '</div>'
            ;
        }

        return $html;
    }

    public function buildBuyerConsent(): string
    {
        return sprintf(
            // translators: %s - rules link
            __(
                'Please be informed that the account information and payment initiation services will be provided to you by Paysera in accordance with these %s. By proceeding with this payment, you agree to receive this service and the service terms and conditions.',
                PayseraPaths::PAYSERA_TRANSLATIONS
            ),
            '<a href="'
            . __('https://www.paysera.com/v2/en/legal/rules-for-the-provision-of-the-payment-initiation-service', PayseraPaths::PAYSERA_TRANSLATIONS)
            . ' " target="_blank" rel="noopener noreferrer"> ' . __('rules', PayseraPaths::PAYSERA_TRANSLATIONS)
            . '</a>'
        );
    }

    /**
     * @param PaymentMethodGroupCollection $groups
     * @param string $countryCode
     * @param bool $isSelected
     * @return string
     */
    private function buildGroupList(PaymentMethodGroupCollection $groups, string $countryCode, bool $isSelected): string
    {
        $translator = new Translator();
        $language = $this->payseraPaymentSettings->getLanguage();

        $html = '';

        /** @var PaymentMethodGroup $group */
        foreach ($groups as $group) {
            $html .= '<div class="payment-group-wrapper"><div class="payment-group-title">' . $translator->getTitle($group, $language)
                . '</div>' . $this->buildMethodsList($group->getPaymentMethods(), $countryCode, $isSelected) . '</div>'
            ;
        }

        return $html;
    }

    /**
     * @param PaymentMethodCollection $methods
     * @param string $countryCode
     * @param bool $isSelected
     * @return string
     */
    private function buildMethodsList(PaymentMethodCollection $methods, string $countryCode, bool $isSelected): string
    {
        $translator = new Translator();
        $language = $this->payseraPaymentSettings->getLanguage();

        $html = '';

        foreach ($methods as $method) {
            $html .= '<div id="' . $method->getKey()
                . '" class="paysera-payment-method"><label class="paysera-payment-method-label"><div><input type="radio" rel="r'
                . $countryCode . $method->getKey() . '" name="payment[pay_type]" value="' . $method->getKey()
                . '"/><span class="paysera-text">' . $translator->getTitle($method, $language)
                . '</span></div><div class="paysera-image"><img src="' . $translator->getLogo($method, $language) . '" ' . 'alt="'
                . $translator->getTitle($method, $language) . '"' . (($isSelected) ? '' : 'loading="lazy"') . '/></div></label></div>'
            ;
        }

        return $html;
    }

    private function getDefaultCountryCode(array $countries, string $countryCode): string
    {
        $countryCodes = [];

        foreach ($countries as $country) {
            $countryCodes[] = $country['code'];
        }

        if (in_array($countryCode, $countryCodes, true) === true) {
            return $countryCode;
        }

        if (in_array('other', $countryCodes, true) === true) {
            return 'other';
        }

        return reset($countries)['code'];
    }
}

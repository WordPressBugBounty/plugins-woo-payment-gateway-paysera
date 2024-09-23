<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Entity;

use Paysera\CheckoutSdk\Entity\Collection\ItemInterface;
use Paysera\CheckoutSdk\Entity\Collection\PaymentMethodGroupCollection;
use Paysera\CheckoutSdk\Entity\Collection\TranslationCollection;
use Paysera\CheckoutSdk\Service\TranslatableTitleInterface;

class PaymentMethodCountry implements ItemInterface, TranslatableTitleInterface
{
    /**
     * Current country code (country code ISO2)
     */
    protected string $countryCode;

    /**
     * Collection of translation objects.
     * @var TranslationCollection<Translation>
     */
    protected TranslationCollection $titleTranslations;

    /**
     * Holds available payment types for this country.
     * @var PaymentMethodGroupCollection<PaymentMethodGroup>
     */
    protected PaymentMethodGroupCollection $groups;

    public function __construct(string $countryCode)
    {
        $this->countryCode = strtolower($countryCode);

        $this->groups = new PaymentMethodGroupCollection();
        $this->titleTranslations = new TranslationCollection();
    }

    /**
     * Returns collection of translation objects.
     * @return TranslationCollection<Translation>
     */
    public function getTitleTranslations(): TranslationCollection
    {
        return $this->titleTranslations;
    }

    public function getFallbackTitle(): string
    {
        return $this->getCode();
    }

    /**
     * Gets country code (country code ISO2).
     */
    public function getCode(): string
    {
        return $this->countryCode;
    }

    /**
     * Returns collection of payment method groups registered for this country.
     * @return PaymentMethodGroupCollection<PaymentMethodGroup>
     */
    public function getGroups(): PaymentMethodGroupCollection
    {
        return $this->groups;
    }

    /**
     * Returns whether this country has no groups.
     */
    public function isEmpty(): bool
    {
        return $this->groups->count() === 0;
    }
}

<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Entity;

use Paysera\CheckoutSdk\Entity\Collection\ItemInterface;
use Paysera\CheckoutSdk\Entity\Collection\TranslationCollection;
use Paysera\CheckoutSdk\Service\TranslatableLogoInterface;
use Paysera\CheckoutSdk\Service\TranslatableTitleInterface;

class PaymentMethod implements ItemInterface, TranslatableTitleInterface, TranslatableLogoInterface
{
    /**
     * Assigned key for this payment method.
     */
    protected string $key;

    /**
     * Collection of translation objects.
     * @var TranslationCollection<Translation>
     */
    protected TranslationCollection $titleTranslations;

    /**
     * Logo collection of objects with urls.
     * Usually logo is same for all languages, but exceptions exist.
     * @var TranslationCollection<Translation>
     */
    protected TranslationCollection $logos;

    public function __construct(
        string $key
    ) {
        $this->key = $key;

        $this->logos = new TranslationCollection();
        $this->titleTranslations = new TranslationCollection();
    }

    /**
     * Get assigned payment method key.
     */
    public function getKey(): string
    {
        return $this->key;
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
        return $this->getKey();
    }

    /**
     * Returns collection of objects with country codes (country code ISO2) and logo urls.
     * @return TranslationCollection<Translation>
     */
    public function getLogos(): TranslationCollection
    {
        return $this->logos;
    }
}

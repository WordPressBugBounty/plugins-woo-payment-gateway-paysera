<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Entity;

use Paysera\CheckoutSdk\Entity\Collection\ItemInterface;
use Paysera\CheckoutSdk\Entity\Collection\PaymentMethodCollection;
use Paysera\CheckoutSdk\Entity\Collection\TranslationCollection;
use Paysera\CheckoutSdk\Service\TranslatableTitleInterface;

class PaymentMethodGroup implements ItemInterface, TranslatableTitleInterface
{
    /**
     * Some unique (in the scope of country) key for this group.
     */
    protected string $key;

    /**
     * Collection of translation objects.
     * @var TranslationCollection<Translation>
     */
    protected TranslationCollection $titleTranslations;

    /**
     * Holds actual payment methods.
     * @var PaymentMethodCollection<PaymentMethod>
     */
    protected PaymentMethodCollection $paymentMethods;

    public function __construct(string $key)
    {
        $this->key = $key;

        $this->paymentMethods = new PaymentMethodCollection();
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
        return $this->getKey();
    }

    /**
     * Returns group key.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Returns available payment methods for this group.
     * @return PaymentMethodCollection<PaymentMethod>
     */
    public function getPaymentMethods(): PaymentMethodCollection
    {
        return $this->paymentMethods;
    }

    /**
     * Returns whether this group has no payment methods.
     */
    public function isEmpty(): bool
    {
        return $this->paymentMethods->count() === 0;
    }
}

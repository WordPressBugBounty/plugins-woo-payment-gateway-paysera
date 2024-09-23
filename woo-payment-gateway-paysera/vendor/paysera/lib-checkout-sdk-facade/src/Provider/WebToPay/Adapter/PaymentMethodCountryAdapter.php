<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Provider\WebToPay\Adapter;

use Paysera\CheckoutSdk\Entity\PaymentMethodCountry;
use Paysera\CheckoutSdk\Entity\Translation;
use Paysera\CheckoutSdk\Util\Invader;
use WebToPay_PaymentMethodCountry;

class PaymentMethodCountryAdapter
{
    protected PaymentMethodGroupAdapter $paymentMethodGroupAdapter;
    protected Invader $invader;

    public function __construct(PaymentMethodGroupAdapter $paymentMethodGroupAdapter, Invader $invader)
    {
        $this->paymentMethodGroupAdapter = $paymentMethodGroupAdapter;
        $this->invader = $invader;
    }

    public function convert(WebToPay_PaymentMethodCountry $providerEntity): PaymentMethodCountry
    {
        $providerEntityProperties = $this->invader->getProperties($providerEntity);

        $paymentMethodCountry = new PaymentMethodCountry((string) $providerEntityProperties['countryCode']);

        foreach ($providerEntityProperties['titleTranslations'] ?? [] as $language => $titleTranslation) {
            $translation = new Translation($language, $titleTranslation);
            $paymentMethodCountry->getTitleTranslations()->append($translation);
        }

        foreach ($providerEntityProperties['groups'] ?? [] as $group) {
            $adaptedGroup = $this->paymentMethodGroupAdapter->convert($group);
            $paymentMethodCountry->getGroups()->append($adaptedGroup);
        }

        return $paymentMethodCountry;
    }
}

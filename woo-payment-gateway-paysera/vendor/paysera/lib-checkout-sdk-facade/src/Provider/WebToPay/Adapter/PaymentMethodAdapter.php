<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Provider\WebToPay\Adapter;

use Paysera\CheckoutSdk\Entity\PaymentMethod;
use Paysera\CheckoutSdk\Entity\Translation;
use Paysera\CheckoutSdk\Util\Invader;
use WebToPay_PaymentMethod;

class PaymentMethodAdapter
{
    protected Invader $invader;

    public function __construct(Invader $invader)
    {
        $this->invader = $invader;
    }

    public function convert(WebToPay_PaymentMethod $providerEntity): PaymentMethod
    {
        $providerEntityProperties = $this->invader->getProperties($providerEntity);

        $paymentMethod = new PaymentMethod((string) $providerEntityProperties['key']);

        foreach ($providerEntityProperties['logoList'] ?? [] as $language => $url) {
            $logo = new Translation($language, $url);
            $paymentMethod->getLogos()->append($logo);
        }

        foreach ($providerEntityProperties['titleTranslations'] ?? [] as $language => $titleTranslation) {
            $translation = new Translation($language, $titleTranslation);
            $paymentMethod->getTitleTranslations()->append($translation);
        }

        return $paymentMethod;
    }
}

<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Provider\WebToPay\Adapter;

use Paysera\CheckoutSdk\Entity\PaymentMethodGroup;
use Paysera\CheckoutSdk\Entity\Translation;
use Paysera\CheckoutSdk\Util\Invader;
use WebToPay_PaymentMethodGroup;

class PaymentMethodGroupAdapter
{
    protected PaymentMethodAdapter $paymentMethodAdapter;
    protected Invader $invader;

    public function __construct(PaymentMethodAdapter $paymentMethodAdapter, Invader $invader)
    {
        $this->paymentMethodAdapter = $paymentMethodAdapter;
        $this->invader = $invader;
    }

    public function convert(WebToPay_PaymentMethodGroup $providerEntity): PaymentMethodGroup
    {
        $providerEntityProperties = $this->invader->getProperties($providerEntity);

        $paymentMethodGroup = new PaymentMethodGroup((string) $providerEntityProperties['groupKey']);

        foreach ($providerEntityProperties['translations'] ?? [] as $language => $titleTranslation) {
            $translation = new Translation($language, $titleTranslation);
            $paymentMethodGroup->getTitleTranslations()->append($translation);
        }

        foreach ($providerEntityProperties['paymentMethods'] ?? [] as $paymentMethod) {
            $adaptedPaymentMethod = $this->paymentMethodAdapter->convert($paymentMethod);
            $paymentMethodGroup->getPaymentMethods()->append($adaptedPaymentMethod);
        }

        return $paymentMethodGroup;
    }
}

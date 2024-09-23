<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Provider\WebToPay\Adapter;

use Paysera\CheckoutSdk\Entity\Request\PaymentCallbackValidationRequest;

class PaymentCallbackValidationRequestNormalizer
{
    public function normalize(PaymentCallbackValidationRequest $request): array
    {
        $validatePaymentData = [
            'data' => $request->getData(),
            'ss1' => $request->getSs1(),
            'ss2' => $request->getSs2(),
        ];

        return array_filter($validatePaymentData, static fn ($value) => $value !== null);
    }
}

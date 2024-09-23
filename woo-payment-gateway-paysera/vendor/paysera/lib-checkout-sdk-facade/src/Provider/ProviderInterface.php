<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Provider;

use Paysera\CheckoutSdk\Entity\Collection\PaymentMethodCountryCollection;
use Paysera\CheckoutSdk\Entity\PaymentMethodCountry;
use Paysera\CheckoutSdk\Entity\Request\PaymentMethodsRequest;
use Paysera\CheckoutSdk\Entity\Request\PaymentRedirectRequest;
use Paysera\CheckoutSdk\Entity\Request\PaymentCallbackValidationRequest;
use Paysera\CheckoutSdk\Entity\PaymentCallbackValidationResponse;
use Paysera\CheckoutSdk\Entity\PaymentRedirectResponse;

interface ProviderInterface
{
    /**
     * @param PaymentMethodsRequest $request
     * @return PaymentMethodCountryCollection<PaymentMethodCountry>
     */
    public function getPaymentMethods(PaymentMethodsRequest $request): PaymentMethodCountryCollection;

    public function getPaymentRedirect(PaymentRedirectRequest $request): PaymentRedirectResponse;

    public function getPaymentCallbackValidatedData(
        PaymentCallbackValidationRequest $request
    ): PaymentCallbackValidationResponse;
}

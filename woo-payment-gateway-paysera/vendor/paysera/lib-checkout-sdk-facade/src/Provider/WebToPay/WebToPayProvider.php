<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Provider\WebToPay;

use Paysera\CheckoutSdk\Entity\Collection\PaymentMethodCountryCollection;
use Paysera\CheckoutSdk\Entity\PaymentMethodCountry;
use Paysera\CheckoutSdk\Entity\Request\PaymentMethodsRequest;
use Paysera\CheckoutSdk\Entity\Request\PaymentRedirectRequest;
use Paysera\CheckoutSdk\Entity\Request\PaymentCallbackValidationRequest;
use Paysera\CheckoutSdk\Entity\PaymentCallbackValidationResponse;
use Paysera\CheckoutSdk\Entity\PaymentRedirectResponse;
use Paysera\CheckoutSdk\Exception\InvalidTypeException;
use Paysera\CheckoutSdk\Exception\ProviderException;
use Paysera\CheckoutSdk\Provider\ProviderInterface;
use Paysera\CheckoutSdk\Provider\WebToPay\Adapter\PaymentCallbackValidationRequestNormalizer;
use Paysera\CheckoutSdk\Provider\WebToPay\Adapter\PaymentMethodCountryAdapter;
use Paysera\CheckoutSdk\Provider\WebToPay\Adapter\PaymentRedirectRequestNormalizer;
use Paysera\CheckoutSdk\Provider\WebToPay\Adapter\PaymentValidationResponseNormalizer;
use WebToPay;
use WebToPay_Factory;
use WebToPayException;

class WebToPayProvider implements ProviderInterface
{
    protected PaymentMethodCountryAdapter $paymentMethodCountryAdapter;
    protected PaymentValidationResponseNormalizer $paymentValidationResponseNormalizer;
    protected PaymentRedirectRequestNormalizer $paymentRedirectRequestNormalizer;
    protected PaymentCallbackValidationRequestNormalizer $callbackValidationRequestNormalizer;

    public function __construct(
        PaymentMethodCountryAdapter $paymentMethodCountryAdapter,
        PaymentValidationResponseNormalizer $paymentValidationResponseNormalizer,
        PaymentRedirectRequestNormalizer $paymentRedirectRequestNormalizer,
        PaymentCallbackValidationRequestNormalizer $callbackValidationRequestNormalizer
    ) {
        $this->paymentMethodCountryAdapter = $paymentMethodCountryAdapter;
        $this->paymentValidationResponseNormalizer = $paymentValidationResponseNormalizer;
        $this->paymentRedirectRequestNormalizer = $paymentRedirectRequestNormalizer;
        $this->callbackValidationRequestNormalizer = $callbackValidationRequestNormalizer;
    }

    /**
     * @param PaymentMethodsRequest $request
     * @return PaymentMethodCountryCollection<PaymentMethodCountry>
     * @throws ProviderException|InvalidTypeException
     */
    public function getPaymentMethods(PaymentMethodsRequest $request): PaymentMethodCountryCollection
    {
        $countryCollection = new PaymentMethodCountryCollection();

        try {
            $countries = WebToPay::getPaymentMethodList(
                $request->getProjectId(),
                $request->getAmount(),
                $request->getCurrency()
            )->getCountries();
            ksort($countries);
        } catch (WebToPayException $exception) {
            throw new ProviderException($exception);
        }

        foreach ($countries as $country) {
            $adaptedPaymentMethodCountry = $this->paymentMethodCountryAdapter->convert($country);
            $countryCollection->append($adaptedPaymentMethodCountry);
        }

        return $countryCollection;
    }

    /**
     * @throws ProviderException
     */
    public function getPaymentRedirect(PaymentRedirectRequest $request): PaymentRedirectResponse
    {
        $paymentData = $this->paymentRedirectRequestNormalizer->normalize($request);

        try {
            $builder = (new WebToPay_Factory(
                [
                    'projectId' => $request->getProjectId(),
                    'password' => $request->getProjectPassword(),
                ]
            ))->getRequestBuilder();

            $redirectUrl = $builder->buildRequestUrlFromData($paymentData);

            return new PaymentRedirectResponse($redirectUrl);
        } catch (WebToPayException $exception) {
            throw new ProviderException($exception);
        }
    }

    public function getPaymentCallbackValidatedData(
        PaymentCallbackValidationRequest $request
    ): PaymentCallbackValidationResponse {
        $validatePaymentData = $this->callbackValidationRequestNormalizer->normalize($request);

        try {
            $response = WebToPay::validateAndParseData(
                $validatePaymentData,
                $request->getProjectId(),
                $request->getProjectPassword()
            );

            return $this->paymentValidationResponseNormalizer->denormalize($response);
        } catch (WebToPayException $exception) {
            throw new ProviderException($exception);
        }
    }
}

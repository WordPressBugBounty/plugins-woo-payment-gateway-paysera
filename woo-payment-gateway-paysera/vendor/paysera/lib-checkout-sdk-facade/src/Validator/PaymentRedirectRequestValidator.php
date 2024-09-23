<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Validator;

use Paysera\CheckoutSdk\Entity\Request\PaymentRedirectRequest;
use Paysera\CheckoutSdk\Entity\RequestInterface;
use Paysera\CheckoutSdk\Exception\InvalidTypeException;
use Paysera\CheckoutSdk\Exception\ValidationException;

class PaymentRedirectRequestValidator implements RequestValidatorInterface
{
    protected CountryCodeIso2Validator $countryCodeIso2Validator;

    public function __construct(CountryCodeIso2Validator $countryCodeIso2Validator)
    {
        $this->countryCodeIso2Validator = $countryCodeIso2Validator;
    }

    public function canValidate(RequestInterface $request): bool
    {
        return $request instanceof PaymentRedirectRequest;
    }

    /**
     * @param PaymentRedirectRequest $request
     * @throws InvalidTypeException|ValidationException
     */
    public function validate(RequestInterface $request): void
    {
        if ($this->canValidate($request) === false) {
            throw new InvalidTypeException(PaymentRedirectRequest::class);
        }

        if ($request->getOrder()->getPayerCountryCode() !== null) {
            $this->countryCodeIso2Validator->validate($request->getOrder()->getPayerCountryCode());
        }
    }
}

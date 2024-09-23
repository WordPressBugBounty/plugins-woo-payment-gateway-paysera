<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Validator;

use Paysera\CheckoutSdk\Entity\Request\PaymentMethodsRequest;
use Paysera\CheckoutSdk\Entity\RequestInterface;
use Paysera\CheckoutSdk\Exception\InvalidTypeException;
use Paysera\CheckoutSdk\Exception\ValidationException;

class PaymentMethodsRequestValidator implements RequestValidatorInterface
{
    public function canValidate(RequestInterface $request): bool
    {
        return $request instanceof PaymentMethodsRequest;
    }

    /**
     * @param PaymentMethodsRequest $request
     * @throws InvalidTypeException|ValidationException
     */
    public function validate(RequestInterface $request): void
    {
        if ($this->canValidate($request) === false) {
            throw new InvalidTypeException(PaymentMethodsRequest::class);
        }

        if (strlen($request->getCurrency()) !== 3) {
            throw new ValidationException('Currency must contain three chars.');
        }
    }
}

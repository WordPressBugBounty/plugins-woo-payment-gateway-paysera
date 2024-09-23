<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Validator;

use Paysera\CheckoutSdk\Entity\Collection\ItemInterface;
use Paysera\CheckoutSdk\Entity\RequestInterface;
use Paysera\CheckoutSdk\Exception\InvalidTypeException;
use Paysera\CheckoutSdk\Exception\ValidationException;

interface RequestValidatorInterface extends ItemInterface
{
    public function canValidate(RequestInterface $request): bool;

    /**
     * @throws InvalidTypeException|ValidationException
     */
    public function validate(RequestInterface $request): void;
}

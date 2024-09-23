<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Exception;

class InvalidTypeException extends BaseException
{
    public function __construct(string $requiredType)
    {
        parent::__construct("Value must be of type `$requiredType`.", static::E_INVALID_TYPE);
    }
}

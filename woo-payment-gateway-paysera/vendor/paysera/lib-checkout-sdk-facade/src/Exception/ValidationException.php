<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Exception;

class ValidationException extends BaseException
{
    public function __construct(string $message)
    {
        parent::__construct($message, static::E_VALIDATION);
    }
}

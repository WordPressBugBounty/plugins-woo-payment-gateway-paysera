<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Exception;

use Throwable;

class ProviderException extends BaseException
{
    public function __construct(Throwable $previous)
    {
        parent::__construct('Provider thrown exception.', static::E_PROVIDER_ISSUE, $previous);
    }
}

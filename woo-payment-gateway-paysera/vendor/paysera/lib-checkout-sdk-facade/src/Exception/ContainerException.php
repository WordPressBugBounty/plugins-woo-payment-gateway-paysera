<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Exception;

use Psr\Container\ContainerExceptionInterface;

class ContainerException extends BaseException implements ContainerExceptionInterface
{
    public function __construct(string $message)
    {
        parent::__construct($message, static::E_CONTAINER);
    }
}

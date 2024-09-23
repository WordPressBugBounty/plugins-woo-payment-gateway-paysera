<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Exception;

use Psr\Container\NotFoundExceptionInterface;

class ContainerNotFoundException extends BaseException implements NotFoundExceptionInterface
{
    public function __construct(string $message)
    {
        parent::__construct($message, static::E_CONTAINER_NOT_FOUND);
    }
}

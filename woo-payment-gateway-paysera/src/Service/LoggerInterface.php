<?php

declare(strict_types=1);

namespace Paysera\Service;

interface LoggerInterface
{
    public function info(string $message): void;

    public function error(string $message, \Throwable $exception = null): void;
}

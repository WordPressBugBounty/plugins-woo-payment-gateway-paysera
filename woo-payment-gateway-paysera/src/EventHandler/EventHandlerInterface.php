<?php

declare(strict_types=1);

namespace Paysera\EventHandler;

interface EventHandlerInterface
{
    /**
     * @param array $payload
     */
    public function handle(array $payload): void;
}

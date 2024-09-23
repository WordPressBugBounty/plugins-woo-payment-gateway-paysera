<?php

namespace Paysera\Helper;

use Paysera\EventHandler\EventHandlerInterface;

class EventHandlingHelper
{
    /**
     * @var array<string, EventHandlerInterface>
     */
    private array $eventHandlers = [];

    public function registerHandler(string $event, EventHandlerInterface $eventHandler): self
    {
        $this->eventHandlers[$event] = $eventHandler;

        return $this;
    }

    /**
     * @param string $event
     * @param array $payload
     */
    public function handle(string $event, array $payload = []): void
    {
        if (isset($this->eventHandlers[$event])) {
            $this->eventHandlers[$event]->handle($payload);
        }
    }
}

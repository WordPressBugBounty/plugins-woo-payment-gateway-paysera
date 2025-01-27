<?php

declare(strict_types=1);

namespace Paysera\Exception;

use Exception;
use Throwable;

class DeliveryActionException extends Exception
{
    private const MESSAGE_TEMPLATE = 'Delivery order %s failed, please create order manually in Paysera system';

    private string $action;

    public function __construct(string $action, Throwable $previous = null)
    {
        $this->action = $action;
        $this->message = sprintf(self::MESSAGE_TEMPLATE, $action);

        parent::__construct($this->message, 0, $previous);
    }

    public function getAction(): string
    {
        return $this->action;
    }
}

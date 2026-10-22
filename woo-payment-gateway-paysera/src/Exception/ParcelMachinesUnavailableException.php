<?php

declare(strict_types=1);

namespace Paysera\Exception;

use Exception;
use Throwable;

class ParcelMachinesUnavailableException extends Exception
{
    public static function merchantClientNotFound(): self
    {
        return new self('Delivery merchant client is not available.');
    }

    public static function requestFailed(Throwable $previous): self
    {
        return new self('Could not fetch parcel machines.', 0, $previous);
    }
}

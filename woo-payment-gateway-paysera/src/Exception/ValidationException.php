<?php

declare(strict_types=1);

namespace Paysera\Exception;

use Exception;

class ValidationException extends Exception
{
    private string $field;

    public function __construct(string $field, string $message)
    {
        $this->field = $field;

        parent::__construct($message);
    }

    public function getField(): string
    {
        return $this->field;
    }
}

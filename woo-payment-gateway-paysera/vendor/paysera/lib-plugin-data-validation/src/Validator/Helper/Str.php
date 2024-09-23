<?php

declare(strict_types=1);

namespace Paysera\DataValidator\Validator\Helper;

class Str
{
    /**
     * @param string|bool $attribute
     */
    public static function prettifyAttributeName($attribute): string
    {
        return ucfirst(str_replace(['.*', '.', '_'], ['', ' ', ' '], $attribute));
    }
}

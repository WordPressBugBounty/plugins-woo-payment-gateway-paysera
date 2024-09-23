<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Util;

use Closure;

class Invader
{
    /**
     * This function can extract all properties (even private and protected) of given object.
     * @param object $object
     * @return array
     */
    public function getProperties(object $object): array
    {
        $function = static function ($object) {
            return get_object_vars($object);
        };
        $invader = Closure::bind($function, null, $object);

        return $invader($object);
    }
}

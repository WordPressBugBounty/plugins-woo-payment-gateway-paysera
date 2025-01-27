<?php

declare(strict_types=1);

namespace Paysera\Entity\Delivery;

use ArrayAccess;
use Mockery\Exception;

abstract class AbstractEntity implements ArrayAccess
{
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        $getter = 'get' . ucfirst($offset);

        return method_exists($this, $getter);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $getter = 'get' . ucfirst($offset);

        if (method_exists($this, $getter)) {
            return $this->$getter();
        }

        throw new Exception('Cannot find the property ' . $offset);
    }

    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $setter = 'set' . ucfirst($offset);

        if (!method_exists($this, $setter)) {
            throw new Exception('Cannot set the property ' . $offset);
        }

        $this->$setter($value);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new Exception('Cannot unset the property ' . $offset);
    }
}

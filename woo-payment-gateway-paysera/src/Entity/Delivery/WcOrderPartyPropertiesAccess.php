<?php

declare(strict_types=1);

namespace Paysera\Entity\Delivery;

trait WcOrderPartyPropertiesAccess
{
    public function getFromWcOrder(string $field): ?string
    {
        $getter = sprintf('get_%s_%s', $this->type, $field);

        return $this->order->{$getter}();
    }

    public function setToWcOrder(string $field, string $value): ?string
    {
        $setter = sprintf('set_%s_%s', $this->type, $field);

        return $this->order->{$setter}($value);
    }
}

<?php

declare(strict_types=1);

namespace Paysera\Provider;

use Paysera\DeliveryApi\MerchantClient\MerchantClient;

interface MerchantClientProviderInterface
{
    public function getMerchantClient(?int $macId, ?string $macSecret): ?MerchantClient;
}

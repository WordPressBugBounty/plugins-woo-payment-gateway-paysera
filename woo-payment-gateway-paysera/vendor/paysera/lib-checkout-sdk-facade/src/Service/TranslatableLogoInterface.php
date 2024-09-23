<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Service;

use Paysera\CheckoutSdk\Entity\Collection\TranslationCollection;

interface TranslatableLogoInterface
{
    public function getLogos(): TranslationCollection;
}

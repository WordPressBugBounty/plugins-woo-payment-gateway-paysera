<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Service;

use Paysera\CheckoutSdk\Entity\Collection\TranslationCollection;

interface TranslatableTitleInterface
{
    public function getTitleTranslations(): TranslationCollection;
    public function getFallbackTitle(): string;
}

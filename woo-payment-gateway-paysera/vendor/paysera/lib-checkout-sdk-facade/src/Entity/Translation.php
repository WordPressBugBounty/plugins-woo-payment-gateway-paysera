<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Entity;

use Paysera\CheckoutSdk\Entity\Collection\ItemInterface;

class Translation implements ItemInterface
{
    /**
     * Current translation language.
     */
    private string $language;

    /**
     * Some translatable text.
     */
    private string $value;

    public function __construct(string $language, string $value)
    {
        $this->language = strtolower($language);
        $this->value = $value;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

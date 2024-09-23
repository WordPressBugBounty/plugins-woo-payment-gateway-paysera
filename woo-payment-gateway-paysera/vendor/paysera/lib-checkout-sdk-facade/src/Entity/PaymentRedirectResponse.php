<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Entity;

class PaymentRedirectResponse
{
    protected string $redirectUrl;

    public function __construct(string $redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }
}

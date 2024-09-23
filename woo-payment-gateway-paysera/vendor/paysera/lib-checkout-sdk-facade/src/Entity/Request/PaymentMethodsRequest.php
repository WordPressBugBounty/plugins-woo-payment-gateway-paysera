<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Entity\Request;

use Paysera\CheckoutSdk\Entity\RequestInterface;

class PaymentMethodsRequest implements RequestInterface
{
    /**
     * Unique project number. Only activated projects can accept payments.
     */
    private int $projectId;

    /**
     * Amount in cents the client has to pay.
     */
    private int $amount;

    /**
     * Payment currency (i.e. USD, EUR, etc.)
     */
    private string $currency;

    public function __construct(int $projectId, int $amount, string $currency)
    {
        $this->projectId = $projectId;
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
}

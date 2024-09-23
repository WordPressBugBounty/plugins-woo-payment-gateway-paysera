<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Entity\Request;

use Paysera\CheckoutSdk\Entity\RequestInterface;

/**
 * @link https://developers.paysera.com/en/checkout/integrations/integration-callback
 */
class PaymentCallbackValidationRequest implements RequestInterface
{
    /**
     * Unique project number. Only activated projects can accept payments.
     */
    private int $projectId;

    /**
     * Unique project password.
     */
    private string $projectPassword;

    /**
     * Encoded parameters from Paysera system.
     */
    private string $data;

    /**
     * Sign of data parameter, without using private-public key scheme.
     */
    private ?string $ss1;

    /**
     * Sign of data parameter, using RSA private-public key scheme with SHA-1 hashing function.
     */
    private ?string $ss2;

    public function __construct(
        int $projectId,
        string $projectPassword,
        string $data
    ) {
        $this->projectId = $projectId;
        $this->projectPassword = $projectPassword;
        $this->data = $data;
        $this->ss1 = null;
        $this->ss2 = null;
    }

    public function getProjectId(): int
    {
        return $this->projectId;
    }

    public function getProjectPassword(): string
    {
        return $this->projectPassword;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function getSs1(): ?string
    {
        return $this->ss1;
    }

    public function setSs1(?string $ss1): self
    {
        $this->ss1 = $ss1;

        return $this;
    }

    public function getSs2(): ?string
    {
        return $this->ss2;
    }

    public function setSs2(?string $ss2): self
    {
        $this->ss2 = $ss2;

        return $this;
    }
}

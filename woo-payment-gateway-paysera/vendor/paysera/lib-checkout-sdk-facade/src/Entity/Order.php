<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Entity;

/**
 * This entity provides the necessary integration fields for order.
 * @link https://developers.paysera.com/en/checkout/integrations/integration-specification
 */
class Order
{
    /**
     * Order number from your system.
     */
    private int $orderId;

    /**
     * Payment currency (i.e. USD, EUR, etc.) you want the client to pay in.
     * If the selected currency cannot be accepted by a specific payment method,
     * the system will convert it automatically to the acceptable currency,
     * according to the currency rate of the day.
     * Payamount and paycurrency answers will be sent to your website.
     */
    private string $currency;


    /**
     * Amount in cents the client has to pay.
     */
    private int $amount;

    /**
     * Payer's name. Requested in the majority of payment methods. Necessary for certain payment methods.
     */
    private ?string $payerFirstName;

    /**
     * Payer's surname. Requested in the majority of payment methods. Necessary for certain payment methods.
     */
    private ?string $payerLastName;

    /**
     * Payer's email address is necessary.
     * If the email address is not received, the client will be requested to enter it.
     * Paysera system will inform the payer about the payment status by this address.
     */
    private ?string $payerEmail;

    /**
     * Payer's address, to which goods will be sent (e.g.: Pilaitės pr. 16). Necessary for certain payment methods.
     */
    private ?string $payerStreet;

    /**
     * Payer's city, to which goods will be sent (e.g.: Vilnius). Necessary for certain payment methods.
     */
    private ?string $payerCity;

    /**
     * Payer's state code (necessary, when buying in the USA). Necessary for certain payment methods.
     */
    private ?string $payerState;

    /**
     * Payer's postal code.
     * Lithuanian postal codes can be found here:
     *     @link https://www.post.lt/pasto-kodu-ir-adresu-paieska
     * Necessary for certain payment methods.
     */
    private ?string $payerZip;

    /**
     * Payer's country code.
     * The list with country codes can be found here:
     *     @link https://en.wikipedia.org/wiki/List_of_ISO_country_codes
     * Necessary for certain payment methods.
     */
    private ?string $payerCountryCode;

    public function __construct(int $orderId, int $amount, string $currency)
    {
        $this->orderId = $orderId;
        $this->amount = $amount;
        $this->currency = strtoupper($currency);
        $this->payerFirstName = null;
        $this->payerLastName = null;
        $this->payerEmail = null;
        $this->payerStreet = null;
        $this->payerCity = null;
        $this->payerState = null;
        $this->payerZip = null;
        $this->payerCountryCode = null;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getPayerFirstName(): ?string
    {
        return $this->payerFirstName;
    }

    public function setPayerFirstName(?string $payerFirstName): self
    {
        $this->payerFirstName = $payerFirstName;

        return $this;
    }

    public function getPayerLastName(): ?string
    {
        return $this->payerLastName;
    }

    public function setPayerLastName(?string $payerLastName): self
    {
        $this->payerLastName = $payerLastName;

        return $this;
    }

    public function getPayerEmail(): ?string
    {
        return $this->payerEmail;
    }

    public function setPayerEmail(?string $payerEmail): self
    {
        $this->payerEmail = $payerEmail;

        return $this;
    }

    public function getPayerStreet(): ?string
    {
        return $this->payerStreet;
    }

    public function setPayerStreet(?string $payerStreet): self
    {
        $this->payerStreet = $payerStreet;

        return $this;
    }

    public function getPayerCity(): ?string
    {
        return $this->payerCity;
    }

    public function setPayerCity(?string $payerCity): self
    {
        $this->payerCity = $payerCity;

        return $this;
    }

    public function getPayerState(): ?string
    {
        return $this->payerState;
    }

    public function setPayerState(?string $payerState): self
    {
        $this->payerState = $payerState;

        return $this;
    }

    public function getPayerZip(): ?string
    {
        return $this->payerZip;
    }

    public function setPayerZip(?string $payerZip): self
    {
        $this->payerZip = $payerZip;

        return $this;
    }

    public function getPayerCountryCode(): ?string
    {
        return $this->payerCountryCode;
    }

    public function setPayerCountryCode(?string $payerCountryCode): self
    {
        $this->payerCountryCode = $payerCountryCode;

        return $this;
    }
}

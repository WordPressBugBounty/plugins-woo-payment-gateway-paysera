<?php

declare(strict_types=1);

namespace Paysera\CheckoutSdk\Service;

/**
 * @link https://developers.paysera.com/en/checkout/integrations/integration-callback
 */
class PaymentStatus
{
    /**
     * Payment has not been executed
     */
    public const NOT_EXECUTED = 0;

    /**
     * Payment successful
     */
    public const SUCCESS = 1;

    /**
     * Payment order accepted, but not yet executed
     */
    public const ACCEPTED = 2;

    /**
     * Additional payment information
     */
    public const ADDITIONAL_INFORMATION = 3;

    /**
     * Payment was executed, but confirmation about received funds in bank won't be sent.
     */
    public const EXECUTED_WITHOUT_CONFIRMATION = 4;

    public const STATUSES = [
        self::NOT_EXECUTED,
        self::SUCCESS,
        self::ACCEPTED,
        self::ADDITIONAL_INFORMATION,
        self::EXECUTED_WITHOUT_CONFIRMATION,
    ];
}

<?php

declare(strict_types=1);

namespace Paysera\Service;

use Evp\Component\Money\Money;
use Paysera\Entity\PayseraPaymentSettings;
use Paysera\Exception\PayseraPaymentRefundException;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\Refund;
use WC_Order;

class RefundAmountCalculator
{
    public function calculateShopRefundAmount(WC_Order $order, Refund $refund): Money
    {
        $orderPaidTotal = $this->createAndValidatePaymentMoney($order);
        $refundValues = new Money($refund->getRefundAmount(), $refund->getRefundCurrency());

        if ($orderPaidTotal->getCurrency() !== $order->get_currency()) {
            return $this->calculateCrossCurrencyRefund(
                $orderPaidTotal,
                $refundValues,
                $order
            );
        }

        return new Money($refund->getRefundAmount(), $order->get_currency());
    }

    private function createAndValidatePaymentMoney(WC_Order $order): Money
    {
        $paymentAmount = $order->get_meta(PayseraPaymentSettings::ORDER_PAYMENT_AMOUNT);
        $paymentCurrency = $order->get_meta(PayseraPaymentSettings::ORDER_PAYMENT_CURRENCY);

        if ($paymentCurrency === null || $paymentCurrency === '' || !is_numeric($paymentAmount)) {
            throw new PayseraPaymentRefundException(
                sprintf(
                    'Payment metadata not found for order #%d. Cannot process refund for order with missing payment information.',
                    $order->get_id()
                ),
                422
            );
        }

        $paymentMoney = Money::createFromMinorUnits($paymentAmount, $paymentCurrency);
        $this->validatePaymentMoney($paymentMoney, $order);

        return $paymentMoney;
    }

    private function validatePaymentMoney(Money $paymentMoney, WC_Order $order): void
    {
        if ($paymentMoney->isZero()) {
            throw new PayseraPaymentRefundException(
                sprintf(
                    'Invalid payment amount for order #%d. Cannot calculate refund ratio with zero payment amount.',
                    $order->get_id()
                ),
                422
            );
        }
    }

    private function calculateCrossCurrencyRefund(
        Money $orderPaidTotal,
        Money $refundValues,
        WC_Order $order
    ): Money {
        $refundRatio = $refundValues->div($orderPaidTotal->getAmount());
        $orderTotal = new Money($order->get_total(), $order->get_currency());
        $shopCalculatedRefundAmount = $orderTotal->mul($refundRatio->getAmount());

        return $shopCalculatedRefundAmount->round()->check();
    }
}

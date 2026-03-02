<?php

declare(strict_types=1);

namespace Paysera\Repository;

use Evp\Component\Money\Money;
use Paysera\Exception\PayseraPaymentRefundException;
use Paysera\Scoped\Paysera\CheckoutSdk\Entity\Refund;
use WC_Order;
use WC_Order_Refund;
use WP_Error;

/**
 * TODO: Make HPOS compatible
 */
class RefundRepository
{
    private const REFUND_ID_HASH_META_KEY = '_paysera_refund_id_hash';
    /**
     * @throws PayseraPaymentRefundException
     */
    public function createRefund(
        WC_Order $order,
        Money $refundAmount,
        Refund $refundData,
        string $reason
    ): WC_Order_Refund {
        $refund = wc_create_refund([
            'amount' => $refundAmount->getAmount(),
            'reason' => $reason,
            'order_id' => $order->get_id(),
            'refund_payment' => false,
            'restock_items' => false,
        ]);

        if ($refund instanceof WP_Error) {
            throw new PayseraPaymentRefundException($refund->get_error_message(), 500);
        }

        $refund->add_meta_data(self::REFUND_ID_HASH_META_KEY, $this->createHashKeyForRefund($refundData));
        $refund->save();

        return $refund;
    }

    private function createHashKeyForRefund(Refund $refund): string
    {
        $data = [
            $refund->getRefundAmount(),
            $refund->getRefundCurrency(),
            $refund->getRefundCommissionAmount(),
            $refund->getRefundCommissionCurrency(),
            $refund->getRefundTimestamp(),
        ];

        return md5(implode('|', array_map(fn($v) => (string) $v, $data)));
    }

    public function refundExistsForCallback(
        WC_Order $order,
        Refund $refundData
    ): bool {

        $args = [
            'type' => 'shop_order_refund',
            'parent' => $order->get_id(),
            'limit' => -1,
            'status' => 'any',
            'meta_query' => [
                [
                    'key' => self::REFUND_ID_HASH_META_KEY,
                    'value' => $this->createHashKeyForRefund($refundData),
                ]
            ],
        ];
        $refunds = wc_get_orders($args);

        return !empty($refunds);
    }
}

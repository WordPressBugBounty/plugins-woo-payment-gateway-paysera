<?php

declare(strict_types=1);

namespace Paysera\Factory;

use Paysera\Scoped\Paysera\DeliverySdk\Collection\OrderItemsCollection;
use Paysera\Scoped\Paysera\DeliverySdk\Exception\InvalidTypeException;
use Paysera\Entity\Delivery\OrderItem;
use Paysera\Provider\PayseraRatesProvider;
use WC_Order;

class OrderItemsCollectionFactory
{
    private PayseraRatesProvider $ratesProvider;

    public function __construct(PayseraRatesProvider $ratesProvider)
    {
        $this->ratesProvider = $ratesProvider;
    }

    /**
     * @throws InvalidTypeException
     */
    public function createFormWcOrder(WC_Order $wcOrder): OrderItemsCollection
    {
        $items = [];
        $weightRate = (int)$this->ratesProvider->getRateByKey(get_option('woocommerce_weight_unit'));
        $dimensionRate = (int)$this->ratesProvider->getRateByKey(get_option('woocommerce_dimension_unit'));

        foreach ($wcOrder->get_items() as $item) {
            $itemData = $item->get_data();
            $product = wc_get_product($itemData['product_id']);

            $productId = $product->is_type('variable') ? $itemData['variation_id'] : $itemData['product_id'];
            $items = array_merge(
                $items,
                $this->getRegularProductOrderItems(
                    $productId,
                    $itemData['quantity'],
                    $weightRate,
                    $dimensionRate,
                )
            );
        }
        return new OrderItemsCollection($items);
    }

    protected function getRegularProductOrderItems($productId, int $quantity, int $weightRate, int $dimsRate): array
    {
        $product = wc_get_product($productId);
        $items = [];
        for ($productQuantity = 1; $productQuantity <= $quantity; $productQuantity++) {
            $items[] = new OrderItem($product, $weightRate, $dimsRate);
        }
        return $items;
    }
}

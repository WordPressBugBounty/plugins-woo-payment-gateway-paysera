<?php

declare(strict_types=1);

namespace Paysera\Entity\Delivery;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderItemInterface;

use WC_Product;

class OrderItem implements MerchantOrderItemInterface
{
    private int $weight;
    private int $length;
    private int $width;
    private int $height;

    public function __construct(WC_Product $product, int $weightRate, int $dimensionRate)
    {
        $this->weight = $this->calculateProductDimension($product->get_weight(), $weightRate);
        $this->length = $this->calculateProductDimension($product->get_length(), $dimensionRate);
        $this->width = $this->calculateProductDimension($product->get_width(), $dimensionRate);
        $this->height = $this->calculateProductDimension($product->get_height(), $dimensionRate);
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    private function calculateProductDimension(string $value, int $rate): int
    {
        return (int) ( ((float) $value) * $rate );
    }
}

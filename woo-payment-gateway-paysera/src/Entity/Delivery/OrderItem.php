<?php

declare(strict_types=1);

namespace Paysera\Entity\Delivery;

use Paysera\Scoped\Paysera\DeliverySdk\Entity\MerchantOrderItemInterface;

use WC_Product;

class OrderItem implements MerchantOrderItemInterface
{
    private WC_Product $product;
    private int $dimensionRate;
    private int $weightRate;
    private int $weight;
    private int $length;
    private int $width;
    private int $height;

    public function __construct(WC_Product $product, int $weightRate, int $dimensionRate)
    {
        $this->product = $product;
        $this->weightRate = $weightRate;
        $this->dimensionRate = $dimensionRate;

        $this->setPropertyFromProduct('weight', $this->weightRate);
        $this->setPropertyFromProduct('length', $this->dimensionRate);
        $this->setPropertyFromProduct('width', $this->dimensionRate);
        $this->setPropertyFromProduct('height', $this->dimensionRate);
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

    private function setPropertyFromProduct(string $property, int $rate): void
    {
        $getter = 'get_' . $property;
        $value = !empty($this->product->$getter()) ? (int)$this->product->$getter() : 0;
        $value *= $rate;
        $this->{$property} = $value;
    }
}

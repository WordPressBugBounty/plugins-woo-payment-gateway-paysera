<?php

declare(strict_types=1);

namespace Paysera\Provider;

defined('ABSPATH') || exit;

class PayseraRatesProvider
{
    private array $rates;

    public function __construct()
    {
        $this->rates = [
            'g' => '1',
            'kg' => '1000',
            'mm' => '1',
            'cm' => '10',
            'm' => '1000',
        ];
    }

    public function getRateByKey(string $key): string
    {
        if (array_key_exists($key, $this->rates)) {
            return $this->rates[$key];
        }

        return '0';
    }
}

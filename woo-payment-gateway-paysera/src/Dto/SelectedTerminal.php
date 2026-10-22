<?php

declare(strict_types=1);

namespace Paysera\Dto;

class SelectedTerminal
{
    private const NOT_SELECTED_VALUE = 'default';

    private string $country;
    private string $city;
    private string $terminalId;

    private function __construct(string $country, string $city, string $terminalId)
    {
        $this->country = $country;
        $this->city = $city;
        $this->terminalId = $terminalId;
    }

    /**
     * @param mixed $country
     * @param mixed $city
     * @param mixed $terminalId
     * @return self|null
     */
    public static function fromValues($country, $city, $terminalId): ?self
    {
        $values = [
            self::normalizeValue($country),
            self::normalizeValue($city),
            self::normalizeValue($terminalId),
        ];

        foreach ($values as $value) {
            if ($value === '' || $value === self::NOT_SELECTED_VALUE) {
                return null;
            }
        }

        return new self(...$values);
    }

    /**
     * @param mixed $value
     * @return string
     */
    private static function normalizeValue($value): string
    {
        return is_string($value) ? sanitize_text_field($value) : '';
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getTerminalId(): string
    {
        return $this->terminalId;
    }
}

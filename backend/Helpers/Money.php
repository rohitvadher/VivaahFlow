<?php

declare(strict_types=1);

namespace App\Helpers;

class Money
{
    public static function toCents($amount): int
    {
        if ($amount === null || $amount === '') {
            return 0;
        }
        $value = (string)$amount;
        $value = trim($value);
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '+-');
        $value = str_replace([',', ' '], '', $value);
        $parts = explode('.', $value, 2);
        $integerPart = $parts[0] === '' ? '0' : (string)$parts[0];
        $decimalPart = isset($parts[1]) ? (string)$parts[1] : '';
        $decimalPart = str_pad(substr($decimalPart . '00', 0, 2), 2, '0');
        $cents = ((int)$integerPart) * 100 + (int)$decimalPart;
        return $negative ? -$cents : $cents;
    }

    public static function fromCents(int $cents): string
    {
        $negative = $cents < 0;
        $absolute = abs($cents);
        $value = sprintf('%d.%02d', intdiv($absolute, 100), $absolute % 100);
        return $negative ? '-' . $value : $value;
    }

    public static function format(int $cents): string
    {
        $symbol = (string)app_config('currency.symbol', '₹');
        $decimals = (int)app_config('currency.decimals', 2);
        $negative = $cents < 0;
        $absolute = abs($cents);
        $formatted = number_format($absolute / 100, $decimals, '.', ',');
        return ($negative ? '- ' : '') . $symbol . $formatted;
    }

    public static function add(int $left, int $right): int
    {
        return $left + $right;
    }

    public static function subtract(int $left, int $right): int
    {
        return $left - $right;
    }

    public static function multiply(int $amount, int $factor): int
    {
        return $amount * $factor;
    }

    public static function percentage(int $amount, int $percent): int
    {
        return (int)round($amount * $percent / 100);
    }
}
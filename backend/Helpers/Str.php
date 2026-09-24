<?php

declare(strict_types=1);

namespace App\Helpers;

class Str
{
    public static function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? $text;
        $text = trim($text, '-');
        return $text === '' ? 'item' : $text;
    }

    public static function truncate(string $text, int $length = 120): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . '...';
    }

    public static function capitalize(string $text): string
    {
        return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
    }

    public static function camelToSlug(string $text): string
    {
        $text = preg_replace('/(?<!^)[A-Z]/', '-$0', $text) ?? $text;
        return strtolower($text);
    }
}
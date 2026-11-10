<?php

declare(strict_types=1);

namespace Phobos\Support;

use DateTimeInterface;

/** Formatação pt-BR e escape de saída (proteção contra XSS nas views). */
final class Format
{
    public static function money(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    public static function number(float $value, int $decimals = 3): string
    {
        return rtrim(rtrim(number_format($value, $decimals, ',', '.'), '0'), ',');
    }

    public static function date(?DateTimeInterface $date): string
    {
        return $date?->format('d/m/Y H:i') ?? '—';
    }

    public static function document(string $digits): string
    {
        return match (strlen($digits)) {
            11 => preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits),
            14 => preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $digits),
            default => $digits,
        };
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

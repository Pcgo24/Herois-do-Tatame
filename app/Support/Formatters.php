<?php

namespace App\Support;

use DateTimeInterface;

class Formatters
{
    public static function cpf(?string $cpf): string
    {
        $digits = self::digits($cpf);

        return strlen($digits) === 11
            ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits)
            : (string) $cpf;
    }

    public static function rg(?string $rg): string
    {
        $digits = self::digits($rg);

        return strlen($digits) === 9
            ? preg_replace('/(\d{2})(\d{3})(\d{3})(\d)/', '$1.$2.$3-$4', $digits)
            : (string) $rg;
    }

    public static function phone(?string $phone): string
    {
        $digits = self::digits($phone);

        return match (strlen($digits)) {
            11 => preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $digits),
            10 => preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $digits),
            default => (string) $phone,
        };
    }

    public static function date(?DateTimeInterface $date): string
    {
        return $date?->format('d/m/Y') ?? '';
    }

    private static function digits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value);
    }
}

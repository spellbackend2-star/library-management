<?php

namespace App\Support;

class Isbn
{
    public static function normalize(string $isbn): string
    {
        return strtoupper((string) preg_replace('/[\s-]+/u', '', trim($isbn)));
    }

    public static function isValid(string $isbn): bool
    {
        $isbn = self::normalize($isbn);

        if (preg_match('/^\d{9}[\dX]$/', $isbn) === 1) {
            $sum = 0;
            for ($index = 0; $index < 10; $index++) {
                $digit = $index === 9 && $isbn[$index] === 'X'
                    ? 10
                    : (int) $isbn[$index];
                $sum += $digit * (10 - $index);
            }

            return $sum % 11 === 0;
        }

        if (preg_match('/^\d{13}$/', $isbn) === 1) {
            $sum = 0;
            for ($index = 0; $index < 13; $index++) {
                $sum += (int) $isbn[$index] * ($index % 2 === 0 ? 1 : 3);
            }

            return $sum % 10 === 0;
        }

        return false;
    }
}

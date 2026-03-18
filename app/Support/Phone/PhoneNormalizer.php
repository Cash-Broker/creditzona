<?php

namespace App\Support\Phone;

class PhoneNormalizer
{
    public static function normalize(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $trimmed);

        if (! is_string($digits) || $digits === '') {
            return $trimmed;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '3590') && strlen($digits) === 13) {
            return '0'.substr($digits, 4);
        }

        if (str_starts_with($digits, '359') && strlen($digits) === 12) {
            return '0'.substr($digits, 3);
        }

        if (strlen($digits) === 9 && ! str_starts_with($digits, '0')) {
            return '0'.$digits;
        }

        return $digits;
    }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidEgn implements ValidationRule
{
    private const WEIGHTS = [2, 4, 8, 5, 10, 9, 7, 3, 6];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $egn = trim($value);

        if (preg_match('/^\d{10}$/', $egn) !== 1) {
            $fail('Невалидно ЕГН.');

            return;
        }

        if (! $this->isValidDate($egn)) {
            $fail('Невалидно ЕГН.');

            return;
        }

        if (! $this->isValidChecksum($egn)) {
            $fail('Невалидно ЕГН.');
        }
    }

    private function isValidDate(string $egn): bool
    {
        $year = (int) substr($egn, 0, 2);
        $month = (int) substr($egn, 2, 2);
        $day = (int) substr($egn, 4, 2);

        if ($month >= 1 && $month <= 12) {
            $year += 1900;
        } elseif ($month >= 21 && $month <= 32) {
            $month -= 20;
            $year += 1800;
        } elseif ($month >= 41 && $month <= 52) {
            $month -= 40;
            $year += 2000;
        } else {
            return false;
        }

        return checkdate($month, $day, $year);
    }

    private function isValidChecksum(string $egn): bool
    {
        $digits = array_map('intval', str_split($egn));

        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $sum += $digits[$i] * self::WEIGHTS[$i];
        }

        $remainder = $sum % 11;
        $expected = $remainder < 10 ? $remainder : 0;

        return $digits[9] === $expected;
    }
}

<?php

namespace App\Rules;

use App\Support\Phone\PhoneNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BulgarianMobilePhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = PhoneNormalizer::normalize($value);

        if (! is_string($normalized) || preg_match('/^0[89]\d{8}$/', $normalized) !== 1) {
            $fail('Моля, въведете валиден мобилен телефонен номер.');
        }
    }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CyrillicText implements ValidationRule
{
    private const MODE_LETTERS_ONLY = 'letters_only';

    private const MODE_WITHOUT_LATIN = 'without_latin';

    private function __construct(
        private readonly string $label,
        private readonly string $mode,
    ) {}

    public static function lettersOnly(string $label): self
    {
        return new self($label, self::MODE_LETTERS_ONLY);
    }

    public static function withoutLatin(string $label): self
    {
        return new self($label, self::MODE_WITHOUT_LATIN);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        if ($this->mode === self::MODE_LETTERS_ONLY) {
            if (preg_match('/^[\p{Cyrillic}\s\-]+$/u', $value) !== 1) {
                $fail("{$this->label} трябва да съдържа само букви на кирилица.");
            }

            return;
        }

        if (preg_match('/[A-Za-z]/', $value) === 1) {
            $fail("{$this->label} не може да съдържа латински букви.");
        }
    }
}

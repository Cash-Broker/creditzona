<?php

namespace App\Http\Requests\Concerns;

use App\Support\Phone\PhoneNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

trait ProtectsPublicForms
{
    protected function preparePublicFormProtection(): void
    {
        $this->merge([
            'website' => $this->normalizeString($this->input('website')),
            'form_started_at' => $this->normalizeTimestamp($this->input('form_started_at')),
        ]);
    }

    /**
     * @return array<string, array<int, Closure|ValidationRule|string>>
     */
    protected function publicFormProtectionRules(): array
    {
        return [
            'website' => ['nullable', 'max:0'],
            'form_started_at' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_int($value) && ! ctype_digit((string) $value)) {
                        return;
                    }

                    $startedAt = Carbon::createFromTimestampMs((int) $value);

                    if ($startedAt->greaterThan(now()->addMinutes(5)) || $startedAt->lt(now()->subDay())) {
                        $fail('Моля, изпратете формата отново.');

                        return;
                    }

                    if ($startedAt->greaterThan(now()->subSeconds(3))) {
                        $fail('Моля, изпратете формата отново.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function publicFormProtectionMessages(): array
    {
        return [
            'website.max' => 'Моля, изпратете формата отново.',
            'form_started_at.required' => 'Моля, изпратете формата отново.',
            'form_started_at.integer' => 'Моля, изпратете формата отново.',
        ];
    }

    protected function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    protected function normalizePhone(mixed $value): ?string
    {
        return PhoneNormalizer::normalize($value);
    }

    private function normalizeTimestamp(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '' || ! ctype_digit($trimmed)) {
            return null;
        }

        return (int) $trimmed;
    }
}

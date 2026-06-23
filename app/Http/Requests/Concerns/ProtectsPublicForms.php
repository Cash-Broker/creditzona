<?php

namespace App\Http\Requests\Concerns;

use App\Support\Forms\FormTimingToken;
use App\Support\Phone\PhoneNormalizer;
use App\Support\Turnstile\TurnstileVerifier;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait ProtectsPublicForms
{
    protected function preparePublicFormProtection(): void
    {
        $this->merge([
            'website' => $this->normalizeString($this->input('website')),
            'form_timing_token' => $this->normalizeString($this->input('form_timing_token')),
            'cf_turnstile_response' => $this->normalizeString($this->input('cf_turnstile_response')),
        ]);
    }

    /**
     * @return array<string, array<int, Closure|ValidationRule|string>>
     */
    protected function publicFormProtectionRules(): array
    {
        return [
            'website' => ['nullable', 'max:0'],
            'form_timing_token' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! FormTimingToken::isValid(is_string($value) ? $value : null)) {
                        $fail('Моля, изпратете формата отново.');
                    }
                },
            ],
            'cf_turnstile_response' => [
                // Required only while Turnstile is configured, so the form keeps
                // working before keys are set. `nullable` covers the disabled
                // case; without the conditional `required`, a null token would
                // short-circuit validation and skip verification entirely.
                Rule::requiredIf(fn (): bool => app(TurnstileVerifier::class)->isEnabled()),
                'nullable',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $verifier = app(TurnstileVerifier::class);

                    if (! $verifier->verify(is_string($value) ? $value : null, $this->ip())) {
                        $fail('Не успяхме да потвърдим заявката. Моля, презаредете страницата и опитайте отново.');
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
            'form_timing_token.required' => 'Моля, изпратете формата отново.',
            'form_timing_token.string' => 'Моля, изпратете формата отново.',
            'cf_turnstile_response.required' => 'Не успяхме да потвърдим заявката. Моля, презаредете страницата и опитайте отново.',
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
}

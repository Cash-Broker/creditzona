<?php

namespace App\Rules;

use App\Models\Lead;
use App\Support\Phone\PhoneNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExclusiveLeadParticipantPhone implements ValidationRule
{
    /**
     * @param  array<int, mixed>  $applicantPhones
     */
    private function __construct(
        private readonly array $applicantPhones = [],
    ) {}

    /**
     * @param  array<int, mixed>  $applicantPhones
     */
    public static function forGuarantor(array $applicantPhones = []): self
    {
        return new self($applicantPhones);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalizedPhone = PhoneNormalizer::normalize($value);

        if (blank($normalizedPhone)) {
            return;
        }

        $applicantPhones = collect($this->applicantPhones)
            ->map(static fn (mixed $phone): ?string => PhoneNormalizer::normalize($phone))
            ->filter(static fn (?string $phone): bool => filled($phone))
            ->values();

        if ($applicantPhones->contains($normalizedPhone)) {
            $fail($this->message());

            return;
        }

        if (Lead::query()->forNormalizedPhone($normalizedPhone)->exists()) {
            $fail($this->message());
        }
    }

    private function message(): string
    {
        return 'Този телефон вече е използван за кредитоискател и не може да се използва и за поръчител.';
    }
}

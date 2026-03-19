<?php

namespace App\Rules;

use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Support\Phone\PhoneNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ExclusiveLeadParticipantPhone implements ValidationRule
{
    private const ROLE_APPLICANT = 'applicant';

    private const ROLE_GUARANTOR = 'guarantor';

    /**
     * @param  array<int, mixed>  $otherPhones
     */
    private function __construct(
        private readonly string $role,
        private readonly array $otherPhones = [],
    ) {}

    /**
     * @param  array<int, mixed>  $guarantorPhones
     */
    public static function forApplicant(array $guarantorPhones = []): self
    {
        return new self(self::ROLE_APPLICANT, $guarantorPhones);
    }

    /**
     * @param  array<int, mixed>  $applicantPhones
     */
    public static function forGuarantor(array $applicantPhones = []): self
    {
        return new self(self::ROLE_GUARANTOR, $applicantPhones);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalizedPhone = PhoneNormalizer::normalize($value);

        if (blank($normalizedPhone)) {
            return;
        }

        $otherPhones = collect($this->otherPhones)
            ->map(static fn (mixed $phone): ?string => PhoneNormalizer::normalize($phone))
            ->filter(static fn (?string $phone): bool => filled($phone))
            ->values();

        if ($otherPhones->contains($normalizedPhone)) {
            $fail($this->message());

            return;
        }

        $existsInOppositeRole = $this->role === self::ROLE_APPLICANT
            ? LeadGuarantor::query()->where('phone', $normalizedPhone)->exists()
            : Lead::query()->forNormalizedPhone($normalizedPhone)->exists();

        if ($existsInOppositeRole) {
            $fail($this->message());
        }
    }

    private function message(): string
    {
        return $this->role === self::ROLE_APPLICANT
            ? 'Този телефон вече е използван за поръчител и не може да се използва и за кредитоискател.'
            : 'Този телефон вече е използван за кредитоискател и не може да се използва и за поръчител.';
    }
}

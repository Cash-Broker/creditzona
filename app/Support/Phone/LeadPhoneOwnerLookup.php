<?php

namespace App\Support\Phone;

use App\Models\Lead;
use App\Models\LeadGuarantor;
use Illuminate\Support\Collection;

class LeadPhoneOwnerLookup
{
    public const ROLE_APPLICANT = 'applicant';

    public const ROLE_GUARANTOR = 'guarantor';

    /**
     * Find leads and guarantors that share the given phone number.
     *
     * @return Collection<int, array{role: string, name: string, phone: string, lead_id: int, guarantor_id: ?int}>
     */
    public static function findOwners(
        ?string $phone,
        ?int $excludingLeadId = null,
        ?int $excludingGuarantorId = null,
    ): Collection {
        $normalizedPhone = PhoneNormalizer::normalize($phone);

        if (blank($normalizedPhone)) {
            return collect();
        }

        $applicants = Lead::query()
            ->forNormalizedPhone($normalizedPhone)
            ->when(
                $excludingLeadId !== null,
                static fn ($query) => $query->where('id', '!=', $excludingLeadId),
            )
            ->get(['id', 'first_name', 'middle_name', 'last_name', 'phone'])
            ->map(static fn (Lead $lead): array => [
                'role' => self::ROLE_APPLICANT,
                'name' => self::composeName($lead->first_name, $lead->middle_name, $lead->last_name),
                'phone' => (string) $lead->phone,
                'lead_id' => (int) $lead->id,
                'guarantor_id' => null,
            ]);

        $guarantors = LeadGuarantor::query()
            ->where('phone', $normalizedPhone)
            ->when(
                $excludingGuarantorId !== null,
                static fn ($query) => $query->where('id', '!=', $excludingGuarantorId),
            )
            ->get(['id', 'lead_id', 'first_name', 'middle_name', 'last_name', 'phone'])
            ->map(static fn (LeadGuarantor $guarantor): array => [
                'role' => self::ROLE_GUARANTOR,
                'name' => self::composeName($guarantor->first_name, $guarantor->middle_name, $guarantor->last_name),
                'phone' => (string) $guarantor->phone,
                'lead_id' => (int) $guarantor->lead_id,
                'guarantor_id' => (int) $guarantor->id,
            ]);

        return $applicants->concat($guarantors)->values();
    }

    private static function composeName(?string $first, ?string $middle, ?string $last): string
    {
        return trim(implode(' ', array_filter([$first, $middle, $last])));
    }
}

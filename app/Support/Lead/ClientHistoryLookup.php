<?php

namespace App\Support\Lead;

use App\Models\Lead;
use App\Support\Phone\PhoneNormalizer;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ClientHistoryLookup
{
    /**
     * Personal/profile fields carried over from a client's previous submissions
     * when they apply again. Deliberately excluded: guarantors, internal notes,
     * messages and emails (conversation history stays on the lead it happened
     * on), documents (the files belong to the old lead's storage lifecycle and
     * would break if shared), and application-specific fields such as amount,
     * credit type and property data, which must come from the new submission.
     *
     * @var list<string>
     */
    public const BACKFILL_FIELDS = [
        'middle_name',
        'egn',
        'email',
        'city',
        'workplace',
        'job_title',
        'salary',
        'marital_status',
        'children_under_18',
        'salary_bank',
        'credit_bank',
        'movable_immovable_property',
    ];

    /**
     * Admin-facing (Bulgarian) labels for BACKFILL_FIELDS, used when reporting
     * which fields a backfill actually filled.
     *
     * @var array<string, string>
     */
    public const BACKFILL_FIELD_LABELS = [
        'middle_name' => 'Презиме',
        'egn' => 'ЕГН',
        'email' => 'Имейл',
        'city' => 'Адрес',
        'workplace' => 'Работодател',
        'job_title' => 'Длъжност',
        'salary' => 'Месечен доход',
        'marital_status' => 'Семейно положение',
        'children_under_18' => 'Деца под 18',
        'salary_bank' => 'Банка за заплатата',
        'credit_bank' => 'Банка по кредита',
        'movable_immovable_property' => 'Движимо/недвижимо имущество',
    ];

    /**
     * Find a client's PREVIOUS lead submissions, matched by normalized phone —
     * the de-facto client identity used across the app (see
     * Lead::scopeForNormalizedPhone, App\Support\Phone\LeadPhoneOwnerLookup and
     * the sticky assignment in LeadService::resolveAssignedUserId).
     *
     * This deliberately bypasses Lead::scopeVisibleToUser. When a returning
     * client is routed to a different consultant (their previous consultant
     * left the pool, became unavailable, or the lead was reassigned), that
     * consultant must be able to review what the client submitted before —
     * the guarantors, their suitability and the recorded notes — instead of
     * re-running checks that were already done. Consumers are responsible for
     * rendering the result read-only and for masking sensitive fields (EGN);
     * no editable PII should cross the consultant boundary.
     *
     * @return Collection<int, Lead>
     */
    public static function previousSubmissions(Lead $lead): Collection
    {
        $normalizedPhone = static::normalizedPhone($lead);

        if ($normalizedPhone === null) {
            return collect();
        }

        return Lead::query()
            ->forNormalizedPhone($normalizedPhone)
            ->whereKeyNot($lead->getKey())
            ->with([
                'assignedUser:id,name',
                'guarantors',
            ])
            ->latest('created_at')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * Collect the most recent non-blank value per BACKFILL_FIELDS entry across
     * the client's earlier submissions (matched by normalized phone). Newer
     * leads win per field, but an older lead still contributes fields the newer
     * ones left blank — e.g. the EGN recorded on the first application must
     * survive an unworked second one.
     *
     * The phone alone is not treated as proof of identity: recycled and
     * family-shared numbers must never attach another data subject's EGN or
     * financial profile to a new person's lead (GDPR accuracy), so only
     * previous leads whose first and last name match the new submission may
     * contribute. A name mismatch simply skips the backfill — the status quo
     * before this feature.
     *
     * Unlike previousSubmissions(), the values returned here are meant to be
     * written onto the client's own new lead — an explicit product decision so
     * returning applicants are not asked for the same data twice. Call it
     * before persisting the new submission: the query does not exclude any
     * lead, so an already-saved new lead would match itself.
     *
     * @return array<string, mixed>
     */
    public static function personalDataDefaults(
        ?string $phone,
        ?string $firstName,
        ?string $lastName,
        ?int $excludeLeadId = null,
    ): array {
        $normalizedPhone = PhoneNormalizer::normalize($phone);
        $normalizedFirstName = static::normalizeNameToken($firstName);
        $normalizedLastName = static::normalizeNameToken($lastName);

        if (blank($normalizedPhone) || $normalizedFirstName === '' || $normalizedLastName === '') {
            return [];
        }

        $previousLeads = Lead::query()
            ->forNormalizedPhone($normalizedPhone)
            ->when(
                $excludeLeadId !== null,
                fn ($query) => $query->whereKeyNot($excludeLeadId),
            )
            ->latest('created_at')
            ->orderByDesc('id')
            ->get(['id', 'first_name', 'last_name', 'source', ...self::BACKFILL_FIELDS]);

        $defaults = [];

        foreach ($previousLeads as $previousLead) {
            if (static::normalizeNameToken($previousLead->first_name) !== $normalizedFirstName
                || static::normalizeNameToken($previousLead->last_name) !== $normalizedLastName) {
                continue;
            }

            foreach (self::BACKFILL_FIELDS as $field) {
                if (array_key_exists($field, $defaults)) {
                    continue;
                }

                // Contact-message leads parse their middle name out of a
                // free-text full_name, so a junk token there must not become
                // the client's official middle name on later applications.
                if ($field === 'middle_name' && $previousLead->source === Lead::SOURCE_CONTACT_MESSAGE) {
                    continue;
                }

                try {
                    $value = $previousLead->{$field};
                } catch (DecryptException) {
                    // Backfill is a convenience; a historic row with an
                    // undecryptable value (key rotation, raw import) must not
                    // abort the client-facing intake flow.
                    Log::warning('Skipping undecryptable lead field during client-history backfill.', [
                        'lead_id' => $previousLead->id,
                        'field' => $field,
                    ]);

                    continue;
                }

                if (! blank($value)) {
                    $defaults[$field] = $value;
                }
            }

            if (count($defaults) === count(self::BACKFILL_FIELDS)) {
                break;
            }
        }

        return $defaults;
    }

    /**
     * The subset of personalDataDefaults() the given attribute set leaves
     * blank — ready to be merged or force-filled into a new lead without
     * overriding anything the current submission provided.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public static function missingPersonalData(array $attributes, ?string $phone, ?int $excludeLeadId = null): array
    {
        $defaults = static::personalDataDefaults(
            $phone,
            $attributes['first_name'] ?? null,
            $attributes['last_name'] ?? null,
            $excludeLeadId,
        );

        return array_filter(
            $defaults,
            fn (mixed $value, string $field): bool => blank($attributes[$field] ?? null),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    private static function normalizeNameToken(?string $value): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';

        return mb_strtolower($collapsed);
    }

    public static function hasPreviousSubmissions(Lead $lead): bool
    {
        $normalizedPhone = static::normalizedPhone($lead);

        if ($normalizedPhone === null) {
            return false;
        }

        return Lead::query()
            ->forNormalizedPhone($normalizedPhone)
            ->whereKeyNot($lead->getKey())
            ->exists();
    }

    private static function normalizedPhone(Lead $lead): ?string
    {
        $normalizedPhone = PhoneNormalizer::normalize($lead->normalized_phone ?: $lead->phone);

        return blank($normalizedPhone) ? null : $normalizedPhone;
    }
}

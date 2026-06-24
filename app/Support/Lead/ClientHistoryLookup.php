<?php

namespace App\Support\Lead;

use App\Models\Lead;
use App\Support\Phone\PhoneNormalizer;
use Illuminate\Support\Collection;

class ClientHistoryLookup
{
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

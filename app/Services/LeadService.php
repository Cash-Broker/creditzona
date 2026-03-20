<?php

namespace App\Services;

use App\Mail\LeadSubmittedConfirmation;
use App\Models\Lead;
use App\Models\User;
use App\Support\Phone\PhoneNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class LeadService
{
    public function __construct(
        private readonly LeadPrivacyConsentPdfService $leadPrivacyConsentPdfService,
    ) {}

    public function createLead(array $data): Lead
    {
        $normalizedPhone = PhoneNormalizer::normalize($data['phone'] ?? null);
        $privacyConsentAccepted = (bool) ($data['privacy_consent'] ?? false);
        $privacyConsentAcceptedAt = $privacyConsentAccepted ? now() : null;
        $privacyConsentSnapshotPath = null;

        $data['phone'] = $normalizedPhone;
        $data['normalized_phone'] = $normalizedPhone;

        try {
            $lead = DB::transaction(function () use (
                $data,
                $privacyConsentAccepted,
                $privacyConsentAcceptedAt,
                &$privacyConsentSnapshotPath,
            ): Lead {
                $isMortgage = ($data['credit_type'] ?? null) === Lead::CREDIT_TYPE_MORTGAGE;
                $assignedUserId = $this->resolveAssignedUserId($data);

                $lead = Lead::create([
                    'credit_type' => $data['credit_type'],
                    'first_name' => $data['first_name'],
                    'middle_name' => $data['middle_name'] ?? null,
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'],
                    'normalized_phone' => $data['normalized_phone'],
                    'email' => $data['email'] ?? null,
                    'city' => $data['city'] ?? null,
                    'workplace' => $data['workplace'] ?? null,
                    'job_title' => $data['job_title'] ?? null,
                    'salary' => $data['salary'] ?? null,
                    'marital_status' => $data['marital_status'] ?? null,
                    'children_under_18' => $data['children_under_18'] ?? null,
                    'salary_bank' => $data['salary_bank'] ?? null,
                    'amount' => $data['amount'],
                    'property_type' => $isMortgage ? ($data['property_type'] ?? null) : null,
                    'property_location' => $isMortgage ? ($data['property_location'] ?? null) : null,
                    'status' => 'new',
                    'assigned_user_id' => $assignedUserId,
                    'additional_user_id' => null,
                    'source' => $data['source'] ?? null,
                    'utm_source' => $data['utm_source'] ?? null,
                    'utm_campaign' => $data['utm_campaign'] ?? null,
                    'utm_medium' => $data['utm_medium'] ?? null,
                    'gclid' => $data['gclid'] ?? null,
                    'privacy_consent_accepted' => $privacyConsentAccepted,
                    'privacy_consent_accepted_at' => $privacyConsentAcceptedAt,
                    'privacy_consent_document_name' => null,
                    'privacy_consent_document_path' => null,
                ]);

                if ($privacyConsentAccepted) {
                    $privacyConsentSnapshot = $this->leadPrivacyConsentPdfService->storeSnapshot($lead);
                    $privacyConsentSnapshotPath = $privacyConsentSnapshot['path'];

                    $lead->forceFill([
                        'privacy_consent_document_name' => $privacyConsentSnapshot['name'],
                        'privacy_consent_document_path' => $privacyConsentSnapshot['path'],
                    ])->save();
                }

                $guarantors = $this->prepareGuarantors($data['guarantors'] ?? null);

                if ($guarantors !== []) {
                    $lead->guarantors()->createMany($guarantors);
                }

                return $lead->loadMissing('assignedUser', 'additionalUser', 'guarantors');
            });
        } catch (\Throwable $exception) {
            if (filled($privacyConsentSnapshotPath)) {
                Storage::disk('local')->delete($privacyConsentSnapshotPath);
            }

            throw $exception;
        }

        $this->sendConfirmationEmail($lead);

        return $lead;
    }

    private function resolveAssignedUserId(array $data): ?int
    {
        if (isset($data['assigned_user_id'])) {
            return $data['assigned_user_id'];
        }

        $eligibleUsers = User::query()
            ->eligibleForLeadPrimaryAssignment()
            ->orderBy('id')
            ->get(['id']);

        if ($eligibleUsers->isEmpty()) {
            return null;
        }

        $eligibleUserIds = $eligibleUsers->pluck('id');

        $historicalLead = Lead::query()
            ->forNormalizedPhone($data['normalized_phone'])
            ->where('created_at', '<', now()->subDays(14))
            ->whereIn('assigned_user_id', $eligibleUserIds)
            ->latest('created_at')
            ->first(['assigned_user_id']);

        if ($historicalLead?->assigned_user_id !== null) {
            return $historicalLead->assigned_user_id;
        }

        $lastAssignedUserId = Lead::query()
            ->whereNotNull('assigned_user_id')
            ->whereIn('assigned_user_id', $eligibleUserIds)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('assigned_user_id');

        if ($lastAssignedUserId === null) {
            return $eligibleUsers->first()->id;
        }

        $currentIndex = $eligibleUsers->search(
            fn (User $user): bool => $user->id === $lastAssignedUserId,
        );

        if ($currentIndex === false) {
            return $eligibleUsers->first()->id;
        }

        $nextIndex = ($currentIndex + 1) % $eligibleUsers->count();

        return $eligibleUsers[$nextIndex]->id;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function prepareGuarantors(mixed $guarantors): array
    {
        if (! is_array($guarantors)) {
            return [];
        }

        return collect($guarantors)
            ->filter(fn (mixed $guarantor): bool => is_array($guarantor))
            ->map(fn (array $guarantor): array => [
                'first_name' => $guarantor['first_name'],
                'last_name' => $guarantor['last_name'],
                'phone' => PhoneNormalizer::normalize($guarantor['phone'] ?? null),
                'status' => $guarantor['status'],
            ])
            ->values()
            ->all();
    }

    private function sendConfirmationEmail(Lead $lead): void
    {
        if (blank($lead->email)) {
            return;
        }

        try {
            Mail::to($lead->email)
                ->send(new LeadSubmittedConfirmation($lead));
        } catch (\Throwable $exception) {
            Log::error('Failed to send lead confirmation email.', [
                'lead_id' => $lead->id,
                'email' => $lead->email,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

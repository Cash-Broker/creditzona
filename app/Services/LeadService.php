<?php

namespace App\Services;

use App\Mail\LeadSubmittedConfirmation;
use App\Models\Lead;
use App\Models\User;
use App\Support\Phone\PhoneNormalizer;
use DomainException;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Filament\Notifications\Notification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification as DatabaseNotificationModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class LeadService
{
    private const LEAD_ASSIGNED_NOTIFICATION_TYPE = 'lead_assigned';

    private const LEAD_ADDITIONAL_ASSIGNED_NOTIFICATION_TYPE = 'lead_additional_assigned';

    private const LEAD_RETURNED_NOTIFICATION_TYPE = 'lead_returned';

    private const LEAD_NOTIFICATION_TYPES = [
        self::LEAD_ASSIGNED_NOTIFICATION_TYPE,
        self::LEAD_ADDITIONAL_ASSIGNED_NOTIFICATION_TYPE,
        self::LEAD_RETURNED_NOTIFICATION_TYPE,
    ];

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
                    'returned_additional_user_id' => null,
                    'returned_to_primary_at' => null,
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
        $this->sendAssignedLeadNotification($lead);

        return $lead;
    }

    public function returnLead(Lead $lead, User $actor): Lead
    {
        if (! ($actor->isAdmin() || $actor->isOperator())) {
            throw new AuthorizationException('Нямате достъп да върнете тази заявка.');
        }

        if ($lead->additional_user_id !== $actor->id) {
            throw new AuthorizationException('Заявката не е закачена към вас.');
        }

        if ($lead->assigned_user_id === null) {
            throw new DomainException('Изберете основен служител, преди да върнете заявката.');
        }

        return DB::transaction(function () use ($lead, $actor): Lead {
            $lead->forceFill([
                'additional_user_id' => null,
                'returned_additional_user_id' => $actor->id,
                'returned_to_primary_at' => now(),
                'returned_to_primary_archived_user_id' => null,
                'returned_to_primary_archived_at' => null,
            ])->save();

            $lead = $lead->refresh();

            $this->deleteLeadNotifications($lead);
            $this->sendReturnedLeadNotification($lead, $actor);

            return $lead;
        });
    }

    public function reassignLead(Lead $lead, User $newOperator, User $actor): Lead
    {
        if (! $actor->isAdmin() && $actor->id !== $lead->assigned_user_id) {
            throw new AuthorizationException('Нямате достъп да прехвърлите тази заявка.');
        }

        if ($newOperator->role !== User::ROLE_OPERATOR) {
            throw new DomainException('Заявката може да бъде прехвърлена само към оператор.');
        }

        if ($newOperator->id === $lead->assigned_user_id) {
            throw new DomainException('Заявката вече е зачислена на този оператор.');
        }

        return DB::transaction(function () use ($lead, $newOperator): Lead {
            $lead->forceFill([
                'assigned_user_id' => $newOperator->id,
            ])->save();

            $lead = $lead->refresh();

            $this->deleteLeadNotifications($lead);
            $this->sendAssignedLeadNotification($lead);

            return $lead;
        });
    }

    public function returnAttachedLeadToPrimary(Lead $lead, User $actor): Lead
    {
        return $this->returnLead($lead, $actor);
    }

    public function archiveAttachedLead(Lead $lead, User $actor): Lead
    {
        if (! ($actor->isAdmin() || $actor->isOperator())) {
            throw new AuthorizationException('Нямате достъп да архивирате тази заявка.');
        }

        if ($lead->additional_user_id !== $actor->id) {
            throw new AuthorizationException('Заявката не е закачена към вас.');
        }

        DB::transaction(function () use ($lead, $actor): void {
            $lead->forceFill([
                'additional_user_id' => null,
                'archived_additional_user_id' => $actor->id,
                'attached_archived_at' => now(),
            ])->save();

            $this->deleteLeadNotifications($lead->refresh());
        });

        return $lead->refresh();
    }

    public function archiveReturnedToPrimaryLead(Lead $lead, User $actor): Lead
    {
        if (! ($actor->isAdmin() || $actor->isOperator())) {
            throw new AuthorizationException('Нямате достъп да архивирате тази върната заявка.');
        }

        if ($lead->assigned_user_id !== $actor->id) {
            throw new AuthorizationException('Тази върната заявка не е към вас.');
        }

        if ($lead->additional_user_id !== null || $lead->returned_to_primary_at === null) {
            throw new DomainException('Само върнати към вас заявки могат да бъдат архивирани.');
        }

        DB::transaction(function () use ($lead, $actor): void {
            $lead->forceFill([
                'returned_to_primary_archived_user_id' => $actor->id,
                'returned_to_primary_archived_at' => now(),
            ])->save();

            $this->deleteLeadNotifications($lead->refresh());
        });

        return $lead->refresh();
    }

    public function approveReturnedLead(Lead $lead, User $actor): Lead
    {
        if (! ($actor->isAdmin() || $actor->isOperator())) {
            throw new AuthorizationException('Нямате достъп да одобрявате тази върната заявка.');
        }

        if (! $actor->isAdmin() && $lead->assigned_user_id !== $actor->id) {
            throw new AuthorizationException('Тази върната заявка не е към вас.');
        }

        if ($lead->returned_to_primary_at === null || $lead->approved_returned_at !== null) {
            throw new DomainException('Само върнати заявки, които все още не са одобрени, могат да бъдат одобрени.');
        }

        DB::transaction(function () use ($lead, $actor): void {
            $lead->forceFill([
                'approved_returned_by_user_id' => $actor->id,
                'approved_returned_at' => now(),
            ])->save();
        });

        return $lead->refresh();
    }

    public function setMarkedForLater(Lead $lead, bool $markedForLater): Lead
    {
        $lead->forceFill([
            'marked_for_later_at' => $markedForLater ? now() : null,
        ])->save();

        return $lead->refresh();
    }

    public function sendAdditionalAssignmentNotification(
        Lead $lead,
        ?int $previousAdditionalUserId = null,
        ?User $actor = null,
    ): void {
        DB::transaction(function () use ($lead, $previousAdditionalUserId, $actor): void {
            if ($lead->additional_user_id !== null && (
                $lead->archived_additional_user_id !== null
                || $lead->attached_archived_at !== null
            )) {
                $lead->forceFill([
                    'archived_additional_user_id' => null,
                    'attached_archived_at' => null,
                ])->save();

                $lead = $lead->refresh();
            }

            if (! Schema::hasTable('notifications')) {
                return;
            }

            if ($previousAdditionalUserId !== null && $previousAdditionalUserId !== $lead->additional_user_id) {
                $this->deleteLeadNotifications($lead, $previousAdditionalUserId);
            }

            if ($lead->additional_user_id === null || $lead->additional_user_id === $previousAdditionalUserId) {
                return;
            }

            $lead->loadMissing('additionalUser');

            $additionalAssignee = $lead->additionalUser;

            if (! $additionalAssignee instanceof User) {
                return;
            }

            $notification = Notification::make()
                ->title('Имате нова заявка към вас')
                ->body($this->formatAdditionalAssignmentNotificationBody($lead, $additionalAssignee, $actor))
                ->warning()
                ->persistent();

            $this->replaceLeadNotifications(
                $lead,
                $additionalAssignee,
                $notification,
                self::LEAD_ADDITIONAL_ASSIGNED_NOTIFICATION_TYPE,
            );
        });
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
            ->where('created_at', '<=', now()->subDays(14))
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

    private function sendAssignedLeadNotification(Lead $lead): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $lead->loadMissing('assignedUser');

        $assignee = $lead->assignedUser;

        if (! $assignee instanceof User) {
            return;
        }

        $notification = Notification::make()
            ->title('Имате нова заявка към вас')
            ->body(sprintf(
                '%s • %s €',
                $this->formatLeadDisplayName($lead),
                number_format((float) $lead->amount, 0, ',', ' '),
            ))
            ->warning()
            ->persistent();

        $this->replaceLeadNotifications(
            $lead,
            $assignee,
            $notification,
            self::LEAD_ASSIGNED_NOTIFICATION_TYPE,
        );
    }

    private function sendReturnedLeadNotification(Lead $lead, User $actor): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $lead->loadMissing('assignedUser');

        $primaryAssignee = $lead->assignedUser;

        if (! $primaryAssignee instanceof User) {
            return;
        }

        if ($primaryAssignee->is($actor)) {
            return;
        }

        $notification = Notification::make()
            ->title('Имате върната заявка към вас')
            ->body(sprintf(
                '%s върна заявката на %s.',
                $actor->name,
                $this->formatLeadDisplayName($lead),
            ))
            ->info()
            ->persistent();

        $this->replaceLeadNotifications(
            $lead,
            $primaryAssignee,
            $notification,
            self::LEAD_RETURNED_NOTIFICATION_TYPE,
        );
    }

    private function replaceLeadNotifications(
        Lead $lead,
        User $recipient,
        Notification $notification,
        string $notificationType,
    ): void {
        $this->deleteLeadNotifications($lead, $recipient->id);
        $this->sendLeadDatabaseNotification($recipient, $lead, $notification, $notificationType);
    }

    private function deleteLeadNotifications(Lead $lead, ?int $notifiableUserId = null): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $this->leadNotificationQuery($lead, $notifiableUserId)->delete();
    }

    private function leadNotificationQuery(Lead $lead, ?int $notifiableUserId = null): Builder
    {
        $query = DatabaseNotificationModel::query()
            ->where('notifiable_type', User::class)
            ->where('type', FilamentDatabaseNotification::class)
            ->where('data->format', 'filament')
            ->where('data->lead_id', $lead->id)
            ->whereIn('data->notification_type', self::LEAD_NOTIFICATION_TYPES);

        if ($notifiableUserId !== null) {
            $query->where('notifiable_id', $notifiableUserId);
        }

        return $query;
    }

    private function sendLeadDatabaseNotification(
        User $recipient,
        Lead $lead,
        Notification $notification,
        string $notificationType,
    ): void {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $recipient->notifyNow(new FilamentDatabaseNotification([
            ...$notification->getDatabaseMessage(),
            'lead_id' => $lead->id,
            'notification_type' => $notificationType,
        ]), ['database']);
    }

    private function formatAdditionalAssignmentNotificationBody(
        Lead $lead,
        User $additionalAssignee,
        ?User $actor = null,
    ): string {
        if ($actor instanceof User && ! $additionalAssignee->is($actor)) {
            return sprintf(
                '%s ви закачи заявката на %s.',
                $actor->name,
                $this->formatLeadDisplayName($lead),
            );
        }

        return sprintf(
            '%s • %s €',
            $this->formatLeadDisplayName($lead),
            number_format((float) $lead->amount, 0, ',', ' '),
        );
    }

    private function formatLeadDisplayName(Lead $lead): string
    {
        $displayName = trim(implode(' ', array_filter([
            $lead->first_name,
            $lead->middle_name,
            $lead->last_name,
        ])));

        return $displayName !== '' ? $displayName : 'клиента';
    }
}

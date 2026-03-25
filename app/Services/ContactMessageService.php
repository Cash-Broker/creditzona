<?php

namespace App\Services;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Lead;
use App\Models\User;
use App\Support\Notes\NoteHistory;
use App\Support\Phone\PhoneNormalizer;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactMessageService
{
    public function storeMessage(array $data): ContactMessage
    {
        $contactMessage = ContactMessage::query()->create([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'message' => $data['message'],
        ]);

        $this->sendNotificationEmail($contactMessage);

        return $contactMessage;
    }

    public function assignToOperator(ContactMessage $contactMessage, User $operator, User $actor): ContactMessage
    {
        if (! $actor->isAdmin()) {
            throw new AuthorizationException('Нямате достъп да закачите това съобщение.');
        }

        if (! $operator->isOperator()) {
            throw new DomainException('Контактните съобщения могат да се закачат само към оператор.');
        }

        if ($contactMessage->archived_at !== null) {
            throw new DomainException('Архивирано съобщение не може да бъде закачано повторно.');
        }

        $contactMessage->forceFill([
            'assigned_user_id' => $operator->id,
        ])->save();

        return $contactMessage->refresh()->loadMissing('assignedUser');
    }

    public function archiveMessage(ContactMessage $contactMessage, User $actor): ContactMessage
    {
        $canArchive = $actor->isAdmin()
            || ($actor->isOperator() && $contactMessage->assigned_user_id === $actor->id);

        if (! $canArchive) {
            throw new AuthorizationException('Нямате достъп да архивирате това съобщение.');
        }

        if ($contactMessage->archived_at !== null) {
            throw new DomainException('Съобщението вече е архивирано.');
        }

        $contactMessage->forceFill([
            'archived_by_user_id' => $actor->id,
            'archived_at' => now(),
        ])->save();

        return $contactMessage->refresh()->loadMissing(['assignedUser', 'archivedByUser']);
    }

    public function createLeadFromMessage(ContactMessage $contactMessage, User $actor): Lead
    {
        $canCreateLead = $actor->isAdmin()
            || ($actor->isOperator() && $contactMessage->assigned_user_id === $actor->id);

        if (! $canCreateLead) {
            throw new AuthorizationException('Нямате достъп да създадете заявка от това съобщение.');
        }

        if ($contactMessage->assigned_user_id === null) {
            throw new DomainException('Закачете съобщението към оператор, преди да създадете заявка.');
        }

        $existingLead = $contactMessage->generatedLead()->first();

        if ($existingLead instanceof Lead) {
            return $existingLead;
        }

        [$firstName, $middleName, $lastName] = $this->splitFullName($contactMessage->full_name);
        $normalizedPhone = PhoneNormalizer::normalize($contactMessage->phone);

        $lead = Lead::query()->create([
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'egn' => null,
            'phone' => $normalizedPhone,
            'normalized_phone' => $normalizedPhone,
            'email' => $contactMessage->email,
            'city' => '',
            'workplace' => null,
            'job_title' => null,
            'salary' => null,
            'marital_status' => null,
            'children_under_18' => null,
            'salary_bank' => null,
            'credit_bank' => null,
            'documents' => null,
            'document_file_names' => null,
            'internal_notes' => NoteHistory::append(
                null,
                $contactMessage->message,
                'Контактна форма',
            ),
            'amount' => 5000,
            'property_type' => null,
            'property_location' => null,
            'status' => 'new',
            'assigned_user_id' => $contactMessage->assigned_user_id,
            'additional_user_id' => null,
            'returned_additional_user_id' => null,
            'returned_to_primary_at' => null,
            'returned_to_primary_archived_user_id' => null,
            'returned_to_primary_archived_at' => null,
            'archived_additional_user_id' => null,
            'attached_archived_at' => null,
            'marked_for_later_at' => null,
            'source' => 'contact_message',
            'utm_source' => null,
            'utm_campaign' => null,
            'utm_medium' => null,
            'gclid' => null,
            'privacy_consent_accepted' => false,
            'privacy_consent_accepted_at' => null,
            'privacy_consent_document_name' => null,
            'privacy_consent_document_path' => null,
        ]);

        $contactMessage->forceFill([
            'generated_lead_id' => $lead->id,
            'lead_generated_at' => now(),
        ])->save();

        return $lead->refresh();
    }

    /**
     * @return array{0: string, 1: ?string, 2: string}
     */
    private function splitFullName(?string $fullName): array
    {
        $parts = preg_split('/\s+/u', trim((string) $fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return ['Клиент', null, 'Неуточнен'];
        }

        if (count($parts) === 1) {
            return [$parts[0], null, 'Неуточнен'];
        }

        $firstName = array_shift($parts) ?? 'Клиент';
        $lastName = array_pop($parts) ?? 'Неуточнен';
        $middleName = $parts !== [] ? implode(' ', $parts) : null;

        return [$firstName, $middleName, $lastName];
    }

    private function sendNotificationEmail(ContactMessage $contactMessage): void
    {
        $recipient = config('mail.contact_recipient');

        if (! is_string($recipient) || trim($recipient) === '') {
            return;
        }

        try {
            Mail::to($recipient)
                ->send(new ContactMessageReceived($contactMessage));
        } catch (\Throwable $exception) {
            Log::error('Failed to send contact message email.', [
                'contact_message_id' => $contactMessage->id,
                'recipient' => $recipient,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

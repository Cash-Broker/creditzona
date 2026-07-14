<?php

namespace App\Services;

use App\Mail\ContactMessageReceived;
use App\Mail\ContactMessageReplyMail;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Models\Lead;
use App\Models\User;
use App\Support\Lead\ClientHistoryLookup;
use App\Support\Notes\NoteHistory;
use App\Support\Phone\PhoneNormalizer;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
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

    public function reply(ContactMessage $contactMessage, User $sender, string $body): ContactMessageReply
    {
        $body = trim($body);

        if ($body === '') {
            throw new DomainException('Съобщението не може да бъде празно.');
        }

        if (blank($contactMessage->email)) {
            throw new DomainException('Това запитване няма имейл адрес, на който да отговорите.');
        }

        if (! ($sender->isAdmin() || $sender->isOperator())) {
            throw new AuthorizationException('Нямате достъп да отговорите на това съобщение.');
        }

        if ($contactMessage->assigned_user_id !== $sender->id) {
            throw new AuthorizationException('Само закаченият оператор може да отговори на това съобщение.');
        }

        if (blank($sender->email)) {
            throw new DomainException('Личният имейл на оператора липсва — не може да се изпрати отговор.');
        }

        $previousReplies = $contactMessage->replies()->orderBy('sent_at')->get();
        $previousMessageIds = $previousReplies
            ->pluck('message_id')
            ->filter()
            ->values()
            ->all();
        $inReplyTo = $previousReplies->last()?->message_id;

        $subject = sprintf(
            'Re: Запитване от %s към CreditZona',
            $contactMessage->full_name,
        );

        return DB::transaction(function () use ($contactMessage, $sender, $body, $subject, $inReplyTo, $previousMessageIds): ContactMessageReply {
            $reply = ContactMessageReply::query()->create([
                'contact_message_id' => $contactMessage->id,
                'sender_user_id' => $sender->id,
                'body' => $body,
                'from_email' => $sender->email,
                'to_email' => $contactMessage->email,
                'subject' => $subject,
                'in_reply_to' => $inReplyTo,
                'sent_at' => now(),
            ]);

            $messageId = sprintf(
                'contact-reply-%d.message-%d@creditzona.bg',
                $reply->id,
                $contactMessage->id,
            );

            $reply->forceFill(['message_id' => $messageId])->save();

            $references = $previousMessageIds;

            if ($inReplyTo !== null && ! in_array($inReplyTo, $references, true)) {
                $references[] = $inReplyTo;
            }

            Mail::to($contactMessage->email)->queue(new ContactMessageReplyMail(
                contactMessage: $contactMessage,
                sender: $sender,
                body: $body,
                subjectLine: $subject,
                messageId: $messageId,
                inReplyTo: $inReplyTo,
                referenceMessageIds: $references,
            ));

            return $reply->refresh();
        });
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
        if ($actor->isAdmin()) {
            return $this->adminArchiveMessage($contactMessage, $actor);
        }

        if (! ($actor->isOperator() && $contactMessage->assigned_user_id === $actor->id)) {
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

    private function adminArchiveMessage(ContactMessage $contactMessage, User $actor): ContactMessage
    {
        if ($contactMessage->admin_archived_at !== null) {
            throw new DomainException('Съобщението вече е архивирано.');
        }

        $contactMessage->forceFill([
            'admin_archived_by_user_id' => $actor->id,
            'admin_archived_at' => now(),
        ])->save();

        return $contactMessage->refresh()->loadMissing(['assignedUser', 'adminArchivedByUser']);
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

        $attributes = [
            'credit_type' => Lead::CREDIT_TYPE_CONSUMER_WITH_GUARANTOR,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'egn' => null,
            'phone' => $normalizedPhone,
            'normalized_phone' => $normalizedPhone,
            'email' => $contactMessage->email,
            'city' => null,
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
            'source' => Lead::SOURCE_CONTACT_MESSAGE,
            'utm_source' => null,
            'utm_campaign' => null,
            'utm_medium' => null,
            'gclid' => null,
            'privacy_consent_accepted' => false,
            'privacy_consent_accepted_at' => null,
            'privacy_consent_document_name' => null,
            'privacy_consent_document_path' => null,
        ];

        // A returning client's personal data (EGN, employment, banks) is
        // carried over from their earlier leads; the message text note above
        // and the contact-message values always stay as submitted.
        $lead = Lead::query()->create(array_merge(
            $attributes,
            ClientHistoryLookup::missingPersonalData($attributes, $normalizedPhone),
        ));

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

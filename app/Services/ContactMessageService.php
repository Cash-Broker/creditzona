<?php

namespace App\Services;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\User;
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

        $contactMessage->forceFill([
            'assigned_user_id' => $operator->id,
        ])->save();

        return $contactMessage->refresh()->loadMissing('assignedUser');
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

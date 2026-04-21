<?php

namespace App\Services;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\CalendarEvent;
use App\Models\ContactMessage;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private const EXPO_PUSH_URL = 'https://exp.host/--/api/v2/push/send';

    private const ICON_URL = 'https://i.imgur.com/r40vawe.jpeg';

    public function notifyNewLead(Lead $lead): array
    {
        $lead->loadMissing('assignedUser');

        if (! $lead->assignedUser instanceof User) {
            return [];
        }

        $name = $this->formatLeadName($lead);
        $amount = number_format((float) $lead->amount, 0, ',', ' ');
        $creditTypeLabel = Lead::getCreditTypeLabel($lead->credit_type);

        return $this->sendToUser(
            $lead->assignedUser,
            '🔔 Нова заявка за кредит!',
            "{$name} · {$amount} лв. · {$creditTypeLabel}",
            ['type' => 'new_lead', 'lead_id' => $lead->id],
        );
    }

    public function notifyStatusChanged(Lead $lead, string $oldStatus, string $newStatus): array
    {
        $lead->loadMissing('assignedUser');

        if (! $lead->assignedUser instanceof User) {
            return [];
        }

        $name = $this->formatLeadName($lead);
        $oldLabel = LeadResource::getStatusLabel($oldStatus);
        $newLabel = LeadResource::getStatusLabel($newStatus);

        return $this->sendToUser(
            $lead->assignedUser,
            '📋 Статус обновен',
            "{$name}: {$oldLabel} → {$newLabel}",
            ['type' => 'status_changed', 'lead_id' => $lead->id],
        );
    }

    public function notifyMarkedForLater(Lead $lead): array
    {
        $lead->loadMissing('assignedUser');

        if (! $lead->assignedUser instanceof User) {
            return [];
        }

        $name = $this->formatLeadName($lead);

        return $this->sendToUser(
            $lead->assignedUser,
            '⏰ Отложен lead',
            "{$name} е отложен за по-късно",
            ['type' => 'marked_for_later', 'lead_id' => $lead->id],
        );
    }

    public function notifyNewContactMessage(ContactMessage $contactMessage): array
    {
        $contactMessage->loadMissing('assignedUser');

        if (! $contactMessage->assignedUser instanceof User) {
            return [];
        }

        return $this->sendToUser(
            $contactMessage->assignedUser,
            '💬 Ново съобщение',
            "{$contactMessage->full_name} · {$contactMessage->phone}",
            ['type' => 'new_contact_message', 'contact_message_id' => $contactMessage->id],
        );
    }

    public function notifyCalendarReminder(CalendarEvent $event): array
    {
        $event->loadMissing('user');

        if (! $event->user instanceof User) {
            return [];
        }

        $timeFormatted = $event->starts_at?->format('H:i') ?? '';

        return $this->sendToUser(
            $event->user,
            '📅 Напомняне',
            "{$event->title} · {$timeFormatted}",
            ['type' => 'calendar_reminder', 'calendar_event_id' => $event->id],
        );
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): array
    {
        if (blank($user->expo_push_token)) {
            return [];
        }

        return $this->sendPushNotifications([
            'to' => $user->expo_push_token,
            'title' => $title,
            'body' => $body,
            'data' => (object) ($data ?: ['type' => 'default']),
            'sound' => 'default',
            'priority' => 'high',
            'channelId' => 'default',
            'icon' => self::ICON_URL,
        ]);
    }

    /**
     * @param  Collection<int, User>  $users
     */
    public function sendToUsers(Collection $users, string $title, string $body, array $data = []): array
    {
        $messages = $users
            ->filter(fn (User $user) => filled($user->expo_push_token))
            ->map(fn (User $user) => [
                'to' => $user->expo_push_token,
                'title' => $title,
                'body' => $body,
                'data' => (object) ($data ?: ['type' => 'default']),
                'sound' => 'default',
                'priority' => 'high',
                'channelId' => 'default',
                'icon' => self::ICON_URL,
            ])
            ->values()
            ->all();

        if ($messages === []) {
            return [];
        }

        return $this->sendPushNotifications($messages);
    }

    private function formatLeadName(Lead $lead): string
    {
        $name = trim(implode(' ', array_filter([
            $lead->first_name,
            $lead->last_name,
        ])));

        return $name !== '' ? $name : 'Клиент';
    }

    private function sendPushNotifications(array $messages): array
    {
        try {
            $response = Http::asJson()
                ->acceptJson()
                ->post(self::EXPO_PUSH_URL, $messages);

            $responseData = $response->json();

            if ($response->failed()) {
                Log::error('Expo push notification request failed.', [
                    'status' => $response->status(),
                    'response' => $responseData,
                    'messages' => $messages,
                ]);
            }

            return $responseData ?? [];
        } catch (\Throwable $exception) {
            Log::error('Expo push notification error.', [
                'error' => $exception->getMessage(),
                'messages' => $messages,
            ]);

            return [];
        }
    }
}

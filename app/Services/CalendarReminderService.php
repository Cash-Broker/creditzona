<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\User;
use Filament\Notifications\DatabaseNotification as FilamentDatabaseNotification;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification as DatabaseNotificationModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class CalendarReminderService
{
    public const NOTIFICATION_TYPE = 'calendar_event_reminder';

    private const NOTIFICATION_RETENTION_HOURS = 24;

    public function dispatchDueRemindersFor(User $user): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $now = now();

        CalendarEvent::query()
            ->where('user_id', $user->id)
            ->where('status', CalendarEvent::STATUS_SCHEDULED)
            ->whereNotNull('reminder_minutes_before')
            ->whereNull('reminder_sent_at')
            ->where('ends_at', '>=', $now)
            ->orderBy('starts_at')
            ->get()
            ->each(function (CalendarEvent $event) use ($user, $now): void {
                $reminderAt = $event->starts_at?->copy()->subMinutes((int) $event->reminder_minutes_before);

                if (! $reminderAt instanceof Carbon || $reminderAt->gt($now)) {
                    return;
                }

                $this->deleteReminderNotificationsForEvent($event, $user->id);
                $this->sendReminderNotification($event, $user);

                $event->forceFill([
                    'reminder_sent_at' => $now,
                ])->save();
            });
    }

    public function cleanupExpiredReminderNotifications(?User $user = null): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $query = $this->reminderNotificationQuery($user?->id)
            ->where('created_at', '<=', now()->subHours(self::NOTIFICATION_RETENTION_HOURS));

        $query->delete();
    }

    public function deleteReminderNotificationsForEvent(CalendarEvent $event, ?int $notifiableUserId = null): void
    {
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $this->reminderNotificationQuery($notifiableUserId)
            ->where('data->calendar_event_id', $event->id)
            ->delete();
    }

    public function resetReminderState(CalendarEvent $event, ?int $previousUserId = null): void
    {
        $event->reminder_sent_at = null;

        $event->save();

        $this->deleteReminderNotificationsForEvent($event, $previousUserId);

        if (($event->user_id !== null) && ($event->user_id !== $previousUserId)) {
            $this->deleteReminderNotificationsForEvent($event, $event->user_id);
        }
    }

    /**
     * @return Builder<DatabaseNotificationModel>
     */
    private function reminderNotificationQuery(?int $notifiableUserId = null): Builder
    {
        $query = DatabaseNotificationModel::query()
            ->where('notifiable_type', User::class)
            ->where('type', FilamentDatabaseNotification::class)
            ->where('data->format', 'filament')
            ->where('data->notification_type', self::NOTIFICATION_TYPE);

        if ($notifiableUserId !== null) {
            $query->where('notifiable_id', $notifiableUserId);
        }

        return $query;
    }

    private function sendReminderNotification(CalendarEvent $event, User $user): void
    {
        $notification = Notification::make()
            ->title('Напомняне за събитие')
            ->body($this->formatReminderBody($event))
            ->warning()
            ->persistent();

        $user->notifyNow(new FilamentDatabaseNotification([
            ...$notification->getDatabaseMessage(),
            'calendar_event_id' => $event->id,
            'notification_type' => self::NOTIFICATION_TYPE,
        ]), ['database']);
    }

    private function formatReminderBody(CalendarEvent $event): string
    {
        if ($event->all_day) {
            return sprintf(
                '%s • %s',
                $event->title,
                $event->starts_at?->translatedFormat('d.m.Y') ?? '',
            );
        }

        return sprintf(
            '%s • %s',
            $event->title,
            $event->starts_at?->translatedFormat('d.m.Y H:i') ?? '',
        );
    }
}

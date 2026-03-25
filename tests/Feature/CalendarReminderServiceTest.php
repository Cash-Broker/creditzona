<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\CalendarEventService;
use App\Services\CalendarReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CalendarReminderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_calendar_event_creates_internal_reminder_notification(): void
    {
        Carbon::setTestNow('2026-03-25 10:00:00');

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
            'name' => 'Анна',
        ]);

        $event = CalendarEvent::query()->create([
            'title' => 'Обаждане към клиент',
            'starts_at' => now()->addMinutes(10),
            'ends_at' => now()->addMinutes(40),
            'all_day' => false,
            'event_type' => CalendarEvent::TYPE_CALL,
            'status' => CalendarEvent::STATUS_SCHEDULED,
            'reminder_minutes_before' => 15,
            'user_id' => $operator->id,
            'created_by_user_id' => $operator->id,
            'updated_by_user_id' => $operator->id,
        ]);

        app(CalendarReminderService::class)->dispatchDueRemindersFor($operator);

        $notification = DB::table('notifications')
            ->where('notifiable_id', $operator->id)
            ->latest('created_at')
            ->first();

        $this->assertNotNull($notification);

        $payload = json_decode((string) $notification->data, true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Напомняне за събитие', $payload['title']);
        $this->assertSame(CalendarReminderService::NOTIFICATION_TYPE, $payload['notification_type']);
        $this->assertSame($event->id, $payload['calendar_event_id']);
        $this->assertNotNull($event->fresh()->reminder_sent_at);

        Carbon::setTestNow();
    }

    public function test_cleanup_expired_calendar_reminder_notifications_deletes_old_rows(): void
    {
        Carbon::setTestNow('2026-03-25 12:00:00');

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $event = CalendarEvent::query()->create([
            'title' => 'Стара среща',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->subDay()->addHour(),
            'all_day' => false,
            'event_type' => CalendarEvent::TYPE_APPOINTMENT,
            'status' => CalendarEvent::STATUS_SCHEDULED,
            'reminder_minutes_before' => 15,
            'reminder_sent_at' => now()->subDay(),
            'user_id' => $operator->id,
            'created_by_user_id' => $operator->id,
            'updated_by_user_id' => $operator->id,
        ]);

        $operator->notifyNow(new \Filament\Notifications\DatabaseNotification([
            ...\Filament\Notifications\Notification::make()
                ->title('Напомняне за събитие')
                ->body('Старо напомняне')
                ->warning()
                ->getDatabaseMessage(),
            'calendar_event_id' => $event->id,
            'notification_type' => CalendarReminderService::NOTIFICATION_TYPE,
        ]), ['database']);

        DB::table('notifications')
            ->where('notifiable_id', $operator->id)
            ->update([
                'created_at' => now()->subHours(30),
                'updated_at' => now()->subHours(30),
            ]);

        app(CalendarReminderService::class)->cleanupExpiredReminderNotifications($operator);

        $this->assertDatabaseCount('notifications', 0);

        Carbon::setTestNow();
    }

    public function test_updating_calendar_event_resets_reminder_state_and_deletes_old_notification(): void
    {
        Carbon::setTestNow('2026-03-25 10:00:00');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $operator = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $event = CalendarEvent::query()->create([
            'title' => 'Среща',
            'starts_at' => now()->addMinutes(10),
            'ends_at' => now()->addMinutes(40),
            'all_day' => false,
            'event_type' => CalendarEvent::TYPE_APPOINTMENT,
            'status' => CalendarEvent::STATUS_SCHEDULED,
            'reminder_minutes_before' => 15,
            'reminder_sent_at' => now(),
            'user_id' => $operator->id,
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        $operator->notifyNow(new \Filament\Notifications\DatabaseNotification([
            ...\Filament\Notifications\Notification::make()
                ->title('Напомняне за събитие')
                ->body('Старо напомняне')
                ->warning()
                ->getDatabaseMessage(),
            'calendar_event_id' => $event->id,
            'notification_type' => CalendarReminderService::NOTIFICATION_TYPE,
        ]), ['database']);

        $updatedEvent = app(CalendarEventService::class)->updateEvent($event, [
            'title' => 'Среща',
            'starts_at' => now()->addDay()->setTime(14, 0)->toDateTimeString(),
            'ends_at' => now()->addDay()->setTime(15, 0)->toDateTimeString(),
            'all_day' => false,
            'event_type' => CalendarEvent::TYPE_APPOINTMENT,
            'status' => CalendarEvent::STATUS_SCHEDULED,
            'reminder_minutes_before' => 30,
            'user_id' => $operator->id,
        ], $admin);

        $this->assertNull($updatedEvent->reminder_sent_at);
        $this->assertDatabaseCount('notifications', 0);

        Carbon::setTestNow();
    }
}

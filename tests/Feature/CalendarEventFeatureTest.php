<?php

namespace Tests\Feature;

use App\Filament\Resources\CalendarEvents\CalendarEventResource;
use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\CalendarEventService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CalendarEventFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_calendar_page(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $this->actingAs($admin)
            ->get(CalendarEventResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Вътрешен календар');
    }

    public function test_calendar_feed_returns_events_in_requested_window_and_filters_by_user(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
            'name' => 'Анна',
        ]);

        $elena = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
            'name' => 'Елена',
        ]);

        CalendarEvent::query()->create([
            'title' => 'Среща с Анна',
            'starts_at' => Carbon::parse('2026-03-28 10:00:00'),
            'ends_at' => Carbon::parse('2026-03-28 11:00:00'),
            'all_day' => false,
            'event_type' => CalendarEvent::TYPE_APPOINTMENT,
            'status' => CalendarEvent::STATUS_SCHEDULED,
            'user_id' => $anna->id,
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        CalendarEvent::query()->create([
            'title' => 'Обаждане с Елена',
            'starts_at' => Carbon::parse('2026-03-28 12:00:00'),
            'ends_at' => Carbon::parse('2026-03-28 12:30:00'),
            'all_day' => false,
            'event_type' => CalendarEvent::TYPE_CALL,
            'status' => CalendarEvent::STATUS_SCHEDULED,
            'user_id' => $elena->id,
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.calendar-events.feed', [
                'start' => '2026-03-28T00:00:00+02:00',
                'end' => '2026-03-29T00:00:00+02:00',
                'user_id' => $anna->id,
            ]));

        $response
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'title' => 'Среща с Анна',
            ]);
    }

    public function test_operator_cannot_move_other_users_event(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $anna = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'anna@creditzona.test',
        ]);

        $elena = User::factory()->create([
            'role' => User::ROLE_OPERATOR,
            'email' => 'elena@creditzona.test',
        ]);

        $event = CalendarEvent::query()->create([
            'title' => 'Чуждо събитие',
            'starts_at' => Carbon::parse('2026-03-28 10:00:00'),
            'ends_at' => Carbon::parse('2026-03-28 11:00:00'),
            'all_day' => false,
            'event_type' => CalendarEvent::TYPE_APPOINTMENT,
            'status' => CalendarEvent::STATUS_SCHEDULED,
            'user_id' => $anna->id,
            'created_by_user_id' => $admin->id,
            'updated_by_user_id' => $admin->id,
        ]);

        $this->actingAs($elena)
            ->patchJson(route('admin.calendar-events.timing.update', ['calendarEvent' => $event]), [
                'starts_at' => '2026-03-28T13:00:00+02:00',
                'ends_at' => '2026-03-28T14:00:00+02:00',
                'all_day' => false,
            ])
            ->assertForbidden();
    }

    public function test_service_normalizes_all_day_events_to_day_boundaries(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'renata@creditzona.test',
        ]);

        $service = app(CalendarEventService::class);

        $event = $service->createEvent([
            'title' => 'Целодневно събитие',
            'starts_at' => '2026-03-28T00:00:00+02:00',
            'ends_at' => '2026-03-30T00:00:00+03:00',
            'all_day' => true,
            'event_type' => CalendarEvent::TYPE_TASK,
            'status' => CalendarEvent::STATUS_SCHEDULED,
            'user_id' => $admin->id,
        ], $admin);

        $this->assertTrue($event->all_day);
        $this->assertSame('00:00:00', $event->starts_at?->format('H:i:s'));
        $this->assertSame('23:59:59', $event->ends_at?->format('H:i:s'));
    }
}

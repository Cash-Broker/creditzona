<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CalendarEventService
{
    public function __construct(
        private readonly CalendarReminderService $calendarReminderService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function listForCalendar(User $viewer, Carbon $visibleStart, Carbon $visibleEnd, array $filters = []): Collection
    {
        /** @var EloquentCollection<int, CalendarEvent> $events */
        $events = CalendarEvent::query()
            ->visibleToUser($viewer)
            ->with(['user'])
            ->where('starts_at', '<', $visibleEnd)
            ->where('ends_at', '>', $visibleStart)
            ->when(filled($filters['user_id'] ?? null), fn ($query) => $query->where('user_id', $filters['user_id']))
            ->when(filled($filters['event_type'] ?? null), fn ($query) => $query->where('event_type', $filters['event_type']))
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->orderBy('starts_at')
            ->get();

        return $events
            ->map(fn (CalendarEvent $event): array => $this->toCalendarPayload($event, $viewer))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createEvent(array $data, User $actor): CalendarEvent
    {
        [$startsAt, $endsAt, $allDay] = $this->normalizeDateRange($data);
        $ownerId = $this->resolveOwnerId($data, $actor);

        $event = new CalendarEvent;
        $event->fill([
            'title' => trim((string) ($data['title'] ?? '')),
            'description' => $this->nullableTrim($data['description'] ?? null),
            'location' => $this->nullableTrim($data['location'] ?? null),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'all_day' => $allDay,
            'event_type' => $data['event_type'] ?? CalendarEvent::TYPE_APPOINTMENT,
            'status' => $data['status'] ?? CalendarEvent::STATUS_SCHEDULED,
            'color' => $this->nullableTrim($data['color'] ?? null),
            'reminder_minutes_before' => $this->normalizeReminderMinutes($data['reminder_minutes_before'] ?? null),
            'reminder_sent_at' => null,
            'user_id' => $ownerId,
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ]);
        $event->save();

        return $event->loadMissing('user', 'createdBy', 'updatedBy');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateEvent(CalendarEvent $event, array $data, User $actor): CalendarEvent
    {
        [$startsAt, $endsAt, $allDay] = $this->normalizeDateRange($data);
        $ownerId = $this->resolveOwnerId($data + ['user_id' => $event->user_id], $actor);
        $reminderMinutes = $this->normalizeReminderMinutes($data['reminder_minutes_before'] ?? $event->reminder_minutes_before);
        $previousUserId = $event->user_id;
        $shouldResetReminderState = $event->starts_at?->ne($startsAt)
            || $event->ends_at?->ne($endsAt)
            || ((bool) $event->all_day !== $allDay)
            || ($event->reminder_minutes_before !== $reminderMinutes)
            || ($previousUserId !== $ownerId)
            || (($data['status'] ?? $event->status) !== CalendarEvent::STATUS_SCHEDULED);

        $event->fill([
            'title' => trim((string) ($data['title'] ?? $event->title)),
            'description' => $this->nullableTrim($data['description'] ?? $event->description),
            'location' => $this->nullableTrim($data['location'] ?? $event->location),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'all_day' => $allDay,
            'event_type' => $data['event_type'] ?? $event->event_type,
            'status' => $data['status'] ?? $event->status,
            'color' => $this->nullableTrim($data['color'] ?? $event->color),
            'reminder_minutes_before' => $reminderMinutes,
            'user_id' => $ownerId,
            'updated_by_user_id' => $actor->id,
        ]);
        $event->save();

        if ($shouldResetReminderState) {
            $this->calendarReminderService->resetReminderState($event, $previousUserId);
        }

        return $event->refresh()->loadMissing('user', 'createdBy', 'updatedBy');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTiming(CalendarEvent $event, array $data, User $actor): CalendarEvent
    {
        [$startsAt, $endsAt, $allDay] = $this->normalizeDateRange($data);
        $shouldResetReminderState = $event->starts_at?->ne($startsAt)
            || $event->ends_at?->ne($endsAt)
            || ((bool) $event->all_day !== $allDay);

        $event->forceFill([
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'all_day' => $allDay,
            'updated_by_user_id' => $actor->id,
        ])->save();

        if ($shouldResetReminderState) {
            $this->calendarReminderService->resetReminderState($event, $event->user_id);
        }

        return $event->refresh()->loadMissing('user', 'createdBy', 'updatedBy');
    }

    public function deleteEvent(CalendarEvent $event): void
    {
        $this->calendarReminderService->deleteReminderNotificationsForEvent($event, $event->user_id);
        $event->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: Carbon, 1: Carbon, 2: bool}
     */
    private function normalizeDateRange(array $data): array
    {
        $timezone = config('app.timezone');

        $startsAt = Carbon::parse((string) ($data['starts_at'] ?? now()), $timezone);
        $endsAt = filled($data['ends_at'] ?? null)
            ? Carbon::parse((string) $data['ends_at'], $timezone)
            : null;
        $allDay = (bool) ($data['all_day'] ?? false);

        // If the user set a specific start time (not midnight), treat as a timed event
        // even if the "all day" toggle is still on from a previous state.
        if ($allDay && $startsAt->format('H:i:s') !== '00:00:00') {
            $allDay = false;
        }

        if ($allDay) {
            $startsAt = $startsAt->copy()->startOfDay();

            if ($endsAt !== null) {
                if ($endsAt->greaterThan($startsAt)) {
                    $endsAt = $endsAt->copy()->subSecond();
                }

                $endsAt = $endsAt->copy()->endOfDay();
            }

            $endsAt ??= $startsAt->copy()->endOfDay();

            if ($endsAt->lt($startsAt)) {
                $endsAt = $startsAt->copy()->endOfDay();
            }

            return [$startsAt, $endsAt, $allDay];
        }

        // Timed event: end time is optional. If missing or invalid, auto-fill with
        // starts + 1 hour so the range query still locates the event.
        if ($endsAt === null || $endsAt->lte($startsAt)) {
            $endsAt = $startsAt->copy()->addHour();
        }

        return [$startsAt, $endsAt, $allDay];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveOwnerId(array $data, User $actor): int
    {
        if ($actor->isAdmin()) {
            $resolved = User::query()
                ->whereKey((int) ($data['user_id'] ?? $actor->id))
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])
                ->value('id');

            if ($resolved === null) {
                throw ValidationException::withMessages([
                    'user_id' => 'Изберете валиден потребител.',
                ]);
            }

            return $resolved;
        }

        return $actor->id;
    }

    private function nullableTrim(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return filled($value) ? $value : null;
    }

    private function normalizeReminderMinutes(mixed $value): ?int
    {
        if (! filled($value)) {
            return null;
        }

        $value = (int) $value;

        return array_key_exists($value, CalendarEvent::getReminderOptions()) ? $value : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function toCalendarPayload(CalendarEvent $event, User $viewer): array
    {
        $color = $event->getResolvedColor();
        $eventEnd = $event->all_day ? $event->ends_at?->copy()->addSecond() : $event->ends_at;

        return [
            'id' => (string) $event->getKey(),
            'title' => $event->title,
            'start' => $event->starts_at?->toIso8601String(),
            'end' => $eventEnd?->toIso8601String(),
            'allDay' => $event->all_day,
            'backgroundColor' => $color,
            'borderColor' => $color,
            'textColor' => '#ffffff',
            'editable' => $viewer->can('update', $event),
            'extendedProps' => [
                'description' => $event->description,
                'location' => $event->location,
                'eventType' => $event->event_type,
                'eventTypeLabel' => $event->getTypeLabel(),
                'status' => $event->status,
                'statusLabel' => $event->getStatusLabel(),
                'reminderLabel' => $event->getReminderLabel(),
                'userId' => $event->user_id,
                'userName' => $event->user?->name,
                'createdBy' => $event->createdBy?->name,
                'updatedBy' => $event->updatedBy?->name,
                'canManage' => $viewer->can('update', $event),
            ],
        ];
    }
}

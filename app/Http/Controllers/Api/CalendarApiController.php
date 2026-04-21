<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreCalendarEventRequest;
use App\Http\Requests\Api\UpdateCalendarEventRequest;
use App\Models\CalendarEvent;
use App\Services\CalendarEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CalendarApiController extends Controller
{
    public function __construct(
        private readonly CalendarEventService $calendarEventService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->filled('date')) {
            $from = Carbon::parse($request->input('date'))->startOfDay();
            $to = $from->copy()->endOfDay();
        } elseif ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->input('from'))->startOfDay();
            $to = Carbon::parse($request->input('to'))->endOfDay();
        } else {
            $from = now()->startOfMonth();
            $to = now()->endOfMonth();
        }

        $events = CalendarEvent::query()
            ->visibleToUser($user)
            ->where('user_id', $user->id)
            ->with('user:id,name')
            ->where('starts_at', '<', $to)
            ->where(function ($q) use ($from) {
                $q->where('ends_at', '>', $from)
                    ->orWhereNull('ends_at');
            })
            ->orderBy('starts_at')
            ->get()
            ->map(fn (CalendarEvent $event) => $this->formatEvent($event));

        return response()->json(['data' => $events]);
    }

    public function store(StoreCalendarEventRequest $request): JsonResponse
    {
        $event = $this->calendarEventService->createEvent(
            $request->validated(),
            $request->user(),
        );

        return response()->json([
            'data' => $this->formatEvent($event),
            'message' => 'Събитието е създадено успешно.',
        ], 201);
    }

    public function update(UpdateCalendarEventRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $event = CalendarEvent::query()->visibleToUser($user)->findOrFail($id);

        if (! $user->isAdmin() && $event->user_id !== $user->id) {
            return response()->json(['message' => 'Нямате достъп да редактирате това събитие.'], 403);
        }

        $event = $this->calendarEventService->updateEvent(
            $event,
            $request->validated(),
            $user,
        );

        return response()->json([
            'data' => $this->formatEvent($event),
            'message' => 'Събитието е обновено успешно.',
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $event = CalendarEvent::query()->visibleToUser($user)->findOrFail($id);

        if (! $user->isAdmin() && $event->user_id !== $user->id) {
            return response()->json(['message' => 'Нямате достъп да изтриете това събитие.'], 403);
        }

        $this->calendarEventService->deleteEvent($event);

        return response()->json(['message' => 'Събитието е изтрито успешно.']);
    }

    public function eventTypes(): JsonResponse
    {
        $types = collect(CalendarEvent::getEventTypeOptions())
            ->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
                'color' => CalendarEvent::getDefaultTypeColors()[$value] ?? '#2563eb',
            ])
            ->values();

        return response()->json(['data' => $types]);
    }

    public function today(Request $request): JsonResponse
    {
        $user = $request->user();

        $events = CalendarEvent::query()
            ->visibleToUser($user)
            ->where('user_id', $user->id)
            ->with('user:id,name')
            ->where('starts_at', '<', now()->endOfDay())
            ->where(function ($q) {
                $q->where('ends_at', '>', now()->startOfDay())
                    ->orWhereNull('ends_at');
            })
            ->orderBy('starts_at')
            ->get()
            ->map(fn (CalendarEvent $event) => $this->formatEvent($event));

        return response()->json(['data' => $events]);
    }

    private function formatEvent(CalendarEvent $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'location' => $event->location,
            'starts_at' => $event->starts_at?->toIso8601String(),
            'ends_at' => $event->ends_at?->toIso8601String(),
            'all_day' => $event->all_day,
            'event_type' => $event->event_type,
            'event_type_label' => $event->getTypeLabel(),
            'status' => $event->status,
            'status_label' => $event->getStatusLabel(),
            'color' => $event->getResolvedColor(),
            'reminder_minutes_before' => $event->reminder_minutes_before,
            'user' => $event->user ? [
                'id' => $event->user->id,
                'name' => $event->user->name,
            ] : null,
            'created_at' => $event->created_at->toIso8601String(),
            'updated_at' => $event->updated_at->toIso8601String(),
        ];
    }
}

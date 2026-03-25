<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\CalendarEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCalendarEventTimingController extends Controller
{
    public function __invoke(
        Request $request,
        CalendarEvent $calendarEvent,
        CalendarEventService $calendarEventService,
    ): JsonResponse {
        $user = $request->user();

        abort_unless($user instanceof User, 403);
        abort_unless($user->can('update', $calendarEvent), 403);

        $validated = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date'],
            'all_day' => ['required', 'boolean'],
        ]);

        $updatedEvent = $calendarEventService->updateTiming($calendarEvent, $validated, $user);

        return response()->json([
            'success' => true,
            'event' => [
                'id' => $updatedEvent->id,
                'starts_at' => $updatedEvent->starts_at?->toIso8601String(),
                'ends_at' => $updatedEvent->ends_at?->toIso8601String(),
                'all_day' => $updatedEvent->all_day,
            ],
        ]);
    }
}

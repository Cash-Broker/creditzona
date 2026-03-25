<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\CalendarEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminCalendarEventFeedController extends Controller
{
    public function __invoke(Request $request, CalendarEventService $calendarEventService): JsonResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);
        abort_unless($user->can('viewAny', CalendarEvent::class), 403);

        $validated = $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'event_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(CalendarEvent::getEventTypeOptions()))],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_keys(CalendarEvent::getStatusOptions()))],
        ]);

        $events = $calendarEventService->listForCalendar(
            $user,
            Carbon::parse($validated['start'], config('app.timezone')),
            Carbon::parse($validated['end'], config('app.timezone')),
            $validated,
        );

        return response()->json($events->all());
    }
}

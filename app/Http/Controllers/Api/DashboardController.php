<?php

namespace App\Http\Controllers\Api;

use App\Filament\Resources\Leads\LeadResource;
use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\ContactMessage;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $baseQuery = Lead::query()->visibleToUser($user);

        $todayCount = (clone $baseQuery)->whereDate('created_at', today())->count();

        $byStatus = (clone $baseQuery)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $statusOptions = LeadResource::getStatusOptions();
        $leadsByStatus = collect($statusOptions)->map(fn (string $label, string $key) => [
            'status' => $key,
            'label' => $label,
            'count' => (int) ($byStatus[$key] ?? 0),
        ])->values();

        $myLeadsCount = Lead::query()
            ->where(function ($q) use ($user) {
                $q->where('assigned_user_id', $user->id)
                    ->orWhere('additional_user_id', $user->id);
            })
            ->count();

        $markedForLaterCount = Lead::query()
            ->visibleToUser($user)
            ->whereNotNull('marked_for_later_at')
            ->count();

        $contactMessagesCount = $user->isAdmin()
            ? ContactMessage::query()->adminActive()->count()
            : ContactMessage::query()->active()->where('assigned_user_id', $user->id)->count();

        $calendarEventsToday = CalendarEvent::query()
            ->where('starts_at', '<', now()->endOfDay())
            ->where(function ($q) {
                $q->where('ends_at', '>', now()->startOfDay())
                    ->orWhereNull('ends_at');
            })
            ->count();

        return response()->json([
            'data' => [
                'leads_today' => $todayCount,
                'leads_by_status' => $leadsByStatus,
                'my_leads_count' => $myLeadsCount,
                'total_leads' => (clone $baseQuery)->count(),
                'marked_for_later_count' => $markedForLaterCount,
                'contact_messages_count' => $contactMessagesCount,
                'calendar_events_today' => $calendarEventsToday,
                'attached_leads_count' => $user->isAdmin() ? 0 : Lead::query()->attachedToUser($user)->count(),
            ],
        ]);
    }
}

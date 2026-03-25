<?php

namespace App\Livewire;

use App\Models\Lead;
use App\Models\User;
use App\Services\CalendarReminderService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class AdminLeadAlerts extends Component
{
    private const RELEVANT_NOTIFICATION_TYPES = [
        'lead_assigned',
        'lead_additional_assigned',
        'lead_returned',
        CalendarReminderService::NOTIFICATION_TYPE,
    ];

    /**
     * @var array<int, string>
     */
    public array $surfacedNotificationIds = [];

    public int $attachedCount = 0;

    public int $returnedToMeCount = 0;

    public ?int $lastReminderSweepAt = null;

    public function mount(): void
    {
        $this->surfacedNotificationIds = array_values(array_filter(
            session()->get('admin_surfaced_lead_notification_ids', []),
            static fn (mixed $id): bool => is_string($id) && $id !== '',
        ));

        $this->refreshState();
    }

    public function refreshState(): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        if ($this->shouldSweepCalendarReminders()) {
            app(CalendarReminderService::class)->dispatchDueRemindersFor($user);
            app(CalendarReminderService::class)->cleanupExpiredReminderNotifications($user);
            $this->lastReminderSweepAt = now()->timestamp;
        }

        $this->attachedCount = Lead::query()->attachedToUser($user)->count();
        $this->returnedToMeCount = $user->isOperator()
            ? Lead::query()->returnedToPrimaryUser($user)->count()
            : 0;

        $this->dispatch(
            'lead-navigation-counts-updated',
            attachedCount: $this->attachedCount,
            returnedToMeCount: $this->returnedToMeCount,
        );

        $notifications = $this->getUnreadRelevantNotifications($user);

        if ($notifications->isEmpty()) {
            return;
        }

        foreach ($notifications as $notification) {
            if (in_array($notification->getKey(), $this->surfacedNotificationIds, true)) {
                continue;
            }

            $data = is_array($notification->data) ? $notification->data : [];

            $this->dispatch(
                'admin-lead-toast',
                title: (string) ($data['title'] ?? ''),
                body: (string) ($data['body'] ?? ''),
                status: (string) ($data['status'] ?? 'info'),
            );

            $this->surfacedNotificationIds[] = $notification->getKey();
        }

        $this->surfacedNotificationIds = array_values(array_unique(array_slice($this->surfacedNotificationIds, -100)));

        session()->put('admin_surfaced_lead_notification_ids', $this->surfacedNotificationIds);
    }

    public function render(): View
    {
        return view('livewire.admin-lead-alerts');
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    private function getUnreadRelevantNotifications(User $user): Collection
    {
        if (! Schema::hasTable('notifications')) {
            return collect();
        }

        /** @var EloquentCollection<int, DatabaseNotification> $notifications */
        $notifications = $user->unreadNotifications()
            ->latest()
            ->limit(20)
            ->get();

        return $notifications
            ->filter(function (DatabaseNotification $notification): bool {
                $data = $notification->data;

                return is_array($data)
                    && ($data['format'] ?? null) === 'filament'
                    && in_array($data['notification_type'] ?? null, self::RELEVANT_NOTIFICATION_TYPES, true);
            })
            ->values();
    }

    private function shouldSweepCalendarReminders(): bool
    {
        return $this->lastReminderSweepAt === null
            || (now()->timestamp - $this->lastReminderSweepAt) >= 60;
    }
}

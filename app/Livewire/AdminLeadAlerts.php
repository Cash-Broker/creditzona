<?php

namespace App\Livewire;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class AdminLeadAlerts extends Component
{
    private const RELEVANT_NOTIFICATION_TITLES = [
        'Имате нова заявка към вас',
        'Имате върната заявка към вас',
    ];

    /**
     * @var array<int, string>
     */
    public array $surfacedNotificationIds = [];

    public int $attachedCount = 0;

    public int $returnedToMeCount = 0;

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

        $this->attachedCount = Lead::query()->attachedToUser($user)->count();
        $this->returnedToMeCount = Lead::query()->returnedToPrimaryUser($user)->count();

        $this->dispatch(
            'lead-navigation-counts-updated',
            attachedCount: $this->attachedCount,
            returnedToMeCount: $this->returnedToMeCount,
        );

        $notifications = $this->getUnreadLeadNotifications($user);

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

    public function render(): string
    {
        return <<<'HTML'
<div
    wire:poll.5s="refreshState"
    class="hidden"
></div>
HTML;
    }

    /**
     * @return Collection<int, DatabaseNotification>
     */
    private function getUnreadLeadNotifications(User $user): Collection
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
                    && in_array($data['title'] ?? null, self::RELEVANT_NOTIFICATION_TITLES, true);
            })
            ->values();
    }
}

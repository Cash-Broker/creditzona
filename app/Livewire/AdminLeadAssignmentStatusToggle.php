<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;

class AdminLeadAssignmentStatusToggle extends Component
{
    public bool $isAvailableForLeadAssignment = true;

    public bool $canToggleOwnAvailability = false;

    public bool $canViewTeamAvailability = false;

    /**
     * @var array<int, array{name: string, is_online: bool}>
     */
    public array $primaryOperatorStatuses = [];

    public function mount(): void
    {
        $this->syncState();
    }

    public function syncState(): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        $user->refresh();

        $this->canToggleOwnAvailability = $user->canToggleLeadAssignmentAvailability();
        $this->canViewTeamAvailability = $user->isAdmin();
        $this->isAvailableForLeadAssignment = $user->isAvailableForLeadAssignment();
        $this->primaryOperatorStatuses = $this->resolvePrimaryOperatorStatuses();
    }

    public function toggleAvailability(): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return;
        }

        if (! $user->canToggleLeadAssignmentAvailability()) {
            return;
        }

        $user->forceFill([
            'is_available_for_lead_assignment' => ! $user->isAvailableForLeadAssignment(),
        ])->save();

        $this->syncState();
    }

    public function render()
    {
        return view('livewire.admin-lead-assignment-status-toggle');
    }

    /**
     * @return array<int, array{name: string, is_online: bool}>
     */
    private function resolvePrimaryOperatorStatuses(): array
    {
        /** @var Collection<int, User> $users */
        $users = User::query()
            ->inLeadPrimaryAssignmentPool()
            ->orderBy('name')
            ->get(['name', 'is_available_for_lead_assignment']);

        return $users
            ->map(fn (User $user): array => [
                'name' => $user->name,
                'is_online' => $user->isAvailableForLeadAssignment(),
            ])
            ->all();
    }
}

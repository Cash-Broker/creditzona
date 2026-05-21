@php
    use App\Models\User;

    $user = auth()->user();

    if (! $user instanceof User || $user->isMarketing()) {
        return;
    }
@endphp

<div>
    @livewire(\App\Livewire\AdminLeadAssignmentStatusToggle::class, [], key('admin-lead-assignment-status-toggle'))
</div>

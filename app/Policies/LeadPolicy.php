<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function view(User $user, Lead $lead): bool
    {
        return true; // All authenticated can view lead record (basic)
    }

    public function update(User $user, Lead $lead): bool
    {
        if ($user->hasRole('boss')) return true;

        // Agents can update only assigned leads
        return (int) $lead->assigned_user_id === (int) $user->id;
    }

    public function viewSensitive(User $user, Lead $lead): bool
    {
        if ($user->hasRole('boss')) return true;
        return (int) $lead->assigned_user_id === (int) $user->id;
    }
}
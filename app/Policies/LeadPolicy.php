<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->canAccessLead($user, $lead);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->canAccessLead($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Lead $lead): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Lead $lead): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    private function canAccessLead(User $user, Lead $lead): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->isOperator()) {
            return false;
        }

        return $lead->assigned_user_id === $user->id
            || $lead->additional_user_id === $user->id
            || (
                $lead->additional_user_id === null
                && $lead->returned_additional_user_id === $user->id
            )
            || (
                $lead->additional_user_id === null
                && $lead->archived_additional_user_id === $user->id
            );
    }
}

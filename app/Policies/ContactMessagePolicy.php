<?php

namespace App\Policies;

use App\Models\ContactMessage;
use App\Models\User;

class ContactMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    public function view(User $user, ContactMessage $contactMessage): bool
    {
        return $user->isAdmin() || $contactMessage->assigned_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ContactMessage $contactMessage): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ContactMessage $contactMessage): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, ContactMessage $contactMessage): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, ContactMessage $contactMessage): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}

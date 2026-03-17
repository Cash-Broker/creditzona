<?php

namespace App\Policies;

use App\Models\AdminDocument;
use App\Models\User;

class AdminDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, AdminDocument $adminDocument): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, AdminDocument $adminDocument): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user, AdminDocument $adminDocument): bool
    {
        return $this->isStaff($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function restore(User $user, AdminDocument $adminDocument): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, AdminDocument $adminDocument): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    private function isStaff(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }
}

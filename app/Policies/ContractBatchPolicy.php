<?php

namespace App\Policies;

use App\Models\ContractBatch;
use App\Models\User;

class ContractBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, ContractBatch $contractBatch): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function update(User $user, ContractBatch $contractBatch): bool
    {
        return $this->isStaff($user);
    }

    public function delete(User $user, ContractBatch $contractBatch): bool
    {
        return $this->isStaff($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function restore(User $user, ContractBatch $contractBatch): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, ContractBatch $contractBatch): bool
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

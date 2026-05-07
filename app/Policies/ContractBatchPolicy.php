<?php

namespace App\Policies;

use App\Models\ContractBatch;
use App\Models\User;

class ContractBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isOperator();
    }

    public function view(User $user, ContractBatch $contractBatch): bool
    {
        if ($user->canViewAllContracts()) {
            return true;
        }

        return $user->isOperator()
            && $contractBatch->attached_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->canViewAllContracts();
    }

    public function update(User $user, ContractBatch $contractBatch): bool
    {
        return $user->canViewAllContracts();
    }

    public function delete(User $user, ContractBatch $contractBatch): bool
    {
        return $user->canViewAllContracts();
    }

    public function deleteAny(User $user): bool
    {
        return $user->canViewAllContracts();
    }

    public function attach(User $user, ContractBatch $contractBatch): bool
    {
        return $user->canViewAllContracts();
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
}

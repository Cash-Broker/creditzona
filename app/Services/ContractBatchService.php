<?php

namespace App\Services;

use App\Models\ContractBatch;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;

class ContractBatchService
{
    public function attachToOperator(ContractBatch $batch, ?User $operator, User $actor): ContractBatch
    {
        if (! $actor->isAdmin()) {
            throw new AuthorizationException('Само админ може да прикачва договори.');
        }

        if ($operator !== null && ! $operator->isOperator()) {
            throw new DomainException('Договорите могат да се прикачат само към оператор.');
        }

        $batch->forceFill([
            'attached_user_id' => $operator?->id,
        ])->save();

        return $batch->refresh()->loadMissing('attachedUser');
    }
}

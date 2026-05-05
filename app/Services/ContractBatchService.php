<?php

namespace App\Services;

use App\Models\ContractBatch;
use App\Models\User;

class ContractBatchService
{
    public function attachToOperator(ContractBatch $batch, ?User $operator, User $actor): ContractBatch
    {
        $batch->forceFill([
            'attached_user_id' => $operator?->id,
        ])->save();

        return $batch->refresh()->loadMissing('attachedUser');
    }
}

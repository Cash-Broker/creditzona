<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadMessage;
use App\Models\User;

class LeadMessageService
{
    public function createMessage(Lead $lead, User $author, array $data): LeadMessage
    {
        return $lead->messages()->create([
            'user_id' => $author->id,
            'body' => $data['body'],
        ])->loadMissing('author');
    }
}

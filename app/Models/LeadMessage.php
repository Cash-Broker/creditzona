<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadMessage extends Model
{
    protected $fillable = [
        'lead_id',
        'user_id',
        'guarantor_id',
        'body',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function guarantor(): BelongsTo
    {
        return $this->belongsTo(LeadGuarantor::class, 'guarantor_id');
    }
}

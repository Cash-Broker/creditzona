<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $fillable = [
        'credit_type',
        'first_name',
        'last_name',
        'phone',
        'email',
        'city',
        'amount',
        'property_type',
        'property_location',
        'status',
        'assigned_user_id',
        'source',
        'utm_source',
        'utm_campaign',
        'utm_medium',
        'gclid',
    ];

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}

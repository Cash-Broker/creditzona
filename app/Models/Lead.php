<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'source',
        'utm_source',
        'utm_campaign',
        'utm_medium',
        'gclid',
    ];
}

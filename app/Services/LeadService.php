<?php

namespace App\Services;

use App\Models\Lead;

class LeadService
{
    public function createLead(array $data): Lead
    {
        $isMortgage = ($data['credit_type'] ?? null) === 'mortgage';

        return Lead::create([
            'credit_type' => $data['credit_type'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'city' => $data['city'],
            'amount' => $data['amount'],
            'property_type' => $isMortgage ? ($data['property_type'] ?? null) : null,
            'property_location' => $isMortgage ? ($data['property_location'] ?? null) : null,
            'status' => 'new',
            'source' => $data['source'] ?? null,
            'utm_source' => $data['utm_source'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'gclid' => $data['gclid'] ?? null,
        ]);
    }
}

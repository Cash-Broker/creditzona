<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\User;

class LeadService
{
    public function createLead(array $data): Lead
    {
        $isMortgage = ($data['credit_type'] ?? null) === 'mortgage';
        $assignedUserId = $this->resolveAssignedUserId($data);

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
            'assigned_user_id' => $assignedUserId,
            'source' => $data['source'] ?? null,
            'utm_source' => $data['utm_source'] ?? null,
            'utm_campaign' => $data['utm_campaign'] ?? null,
            'utm_medium' => $data['utm_medium'] ?? null,
            'gclid' => $data['gclid'] ?? null,
        ]);
    }

    private function resolveAssignedUserId(array $data): ?int
    {
        if (isset($data['assigned_user_id'])) {
            return $data['assigned_user_id'];
        }

        $historicalLead = Lead::query()
            ->where('phone', $data['phone'])
            ->where('created_at', '<', now()->subDays(14))
            ->latest('created_at')
            ->first(['assigned_user_id']);

        if ($historicalLead?->assigned_user_id !== null) {
            return $historicalLead->assigned_user_id;
        }

        $eligibleUsers = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])
            ->orderBy('id')
            ->get(['id']);

        if ($eligibleUsers->isEmpty()) {
            return null;
        }

        $eligibleUserIds = $eligibleUsers->pluck('id');

        $lastAssignedUserId = Lead::query()
            ->whereNotNull('assigned_user_id')
            ->whereIn('assigned_user_id', $eligibleUserIds)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->value('assigned_user_id');

        if ($lastAssignedUserId === null) {
            return $eligibleUsers->first()->id;
        }

        $currentIndex = $eligibleUsers->search(
            fn (User $user): bool => $user->id === $lastAssignedUserId,
        );

        if ($currentIndex === false) {
            return $eligibleUsers->first()->id;
        }

        $nextIndex = ($currentIndex + 1) % $eligibleUsers->count();

        return $eligibleUsers[$nextIndex]->id;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],

            'service_type' => ['required', 'in:consumer,mortgage,refinance,debt_buyout'],
            'amount' => ['nullable', 'integer', 'min:0', 'max:2000000'],
            'term_months' => ['nullable', 'integer', 'min:1', 'max:480'],

            // Sensitive
            'egn' => ['required', 'string', 'min:10', 'max:10'],
            'monthly_income' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'employment_type' => ['nullable', 'in:contract,self_employed,pensioner,unemployed'],
            'monthly_debt' => ['nullable', 'integer', 'min:0', 'max:1000000'],

            // GDPR
            'consent' => ['required', 'accepted'],
        ]);

        // Auto-assign: round-robin by last assigned lead id
        $agentIds = User::role('agent')->orderBy('id')->pluck('id')->values();
        $assignedUserId = null;

        if ($agentIds->count() > 0) {
            $lastLead = Lead::whereNotNull('assigned_user_id')->latest('id')->first();
            if (!$lastLead) {
                $assignedUserId = $agentIds->first();
            } else {
                $currentIndex = $agentIds->search($lastLead->assigned_user_id);
                $nextIndex = ($currentIndex === false) ? 0 : (($currentIndex + 1) % $agentIds->count());
                $assignedUserId = $agentIds[$nextIndex];
            }
        }

        Lead::create([
            ...$validated,
            'assigned_user_id' => $assignedUserId,
            'status' => 'new',
            'priority' => 2,
            'source' => $request->input('source'),
            'utm_source' => $request->input('utm_source'),
            'utm_campaign' => $request->input('utm_campaign'),
            'utm_medium' => $request->input('utm_medium'),
            'gclid' => $request->input('gclid'),
            'consent_at' => now(),
            'consent_ip' => $request->ip(),
            'consent_user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return back()->with('ok', 'Благодарим! Ще се свържем с вас възможно най-скоро.');
    }
}
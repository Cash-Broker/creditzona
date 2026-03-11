<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'credit_type' => ['required', 'in:consumer,mortgage'],
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'integer', 'min:5000', 'max:50000'],
        ]);

        Lead::create([
            'credit_type' => $validated['credit_type'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'city' => $validated['city'],
            'amount' => $validated['amount'],
            'status' => 'new',
            'source' => $request->input('source'),
            'utm_source' => $request->input('utm_source'),
            'utm_campaign' => $request->input('utm_campaign'),
            'utm_medium' => $request->input('utm_medium'),
            'gclid' => $request->input('gclid'),
        ]);

        $successMessage = 'Благодарим! Ще се свържем с вас до 48ч.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $successMessage,
            ]);
        }

        return back()->with('ok', $successMessage);
    }
}

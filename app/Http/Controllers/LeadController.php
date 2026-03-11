<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class LeadController extends Controller
{
    public function __construct(
        private readonly LeadService $leadService
    ) {
    }

    public function store(StoreLeadRequest $request): JsonResponse|RedirectResponse
    {
        $successMessage = 'Благодарим! Ще се свържем с вас до 48ч.';
        $errorMessage = 'Възникна грешка при изпращането на заявката. Моля, опитайте отново.';

        try {
            $leadData = array_merge(
                $request->validated(),
                $request->only([
                    'source',
                    'utm_source',
                    'utm_campaign',
                    'utm_medium',
                    'gclid',
                ])
            );

            $this->leadService->createLead($leadData);
        } catch (\Throwable $exception) {
            Log::error('Failed to store lead.', [
                'error' => $exception->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $errorMessage,
                ], 422);
            }

            return back()->withErrors([
                'lead' => $errorMessage,
            ])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $successMessage,
            ]);
        }

        return back()->with('ok', $successMessage);
    }
}

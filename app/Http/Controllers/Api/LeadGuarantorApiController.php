<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Services\LeadPrivacyConsentPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadGuarantorApiController extends Controller
{
    public function index(Request $request, int $leadId): JsonResponse
    {
        $lead = Lead::query()
            ->visibleToUser($request->user())
            ->findOrFail($leadId);

        $guarantors = $lead->guarantors->map(fn (LeadGuarantor $g) => $this->format($g));

        return response()->json(['data' => $guarantors]);
    }

    public function store(Request $request, int $leadId): JsonResponse
    {
        $lead = Lead::query()
            ->visibleToUser($request->user())
            ->findOrFail($leadId);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'egn' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:255',
            'workplace' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'salary' => 'nullable|integer|min:0',
            'amount' => 'nullable|integer|min:0',
            'marital_status' => 'nullable|string|in:single,married,divorced,widowed,cohabiting',
            'marital_status_note' => 'nullable|string|max:500',
            'children_under_18' => 'nullable|integer|min:0',
            'salary_bank' => 'nullable|string|max:255',
            'credit_bank' => 'nullable|string|max:255',
            'property_type' => 'nullable|string|max:255',
            'property_location' => 'nullable|string|max:255',
            'internal_notes' => 'nullable|string',
        ]);

        $guarantor = $lead->guarantors()->create($validated);

        return response()->json([
            'data' => $this->format($guarantor),
            'message' => 'Поръчителят е добавен.',
        ], 201);
    }

    public function update(Request $request, int $leadId, int $guarantorId): JsonResponse
    {
        $lead = Lead::query()
            ->visibleToUser($request->user())
            ->findOrFail($leadId);

        $guarantor = $lead->guarantors()->findOrFail($guarantorId);

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'egn' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:255',
            'workplace' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'salary' => 'nullable|integer|min:0',
            'amount' => 'nullable|integer|min:0',
            'status' => 'nullable|string|in:suitable,unsuitable,declined',
            'marital_status' => 'nullable|string|in:single,married,divorced,widowed,cohabiting',
            'marital_status_note' => 'nullable|string|max:500',
            'children_under_18' => 'nullable|integer|min:0',
            'salary_bank' => 'nullable|string|max:255',
            'credit_bank' => 'nullable|string|max:255',
            'property_type' => 'nullable|string|max:255',
            'property_location' => 'nullable|string|max:255',
            'internal_notes' => 'nullable|string',
        ]);

        $guarantor->update($validated);

        return response()->json([
            'data' => $this->format($guarantor->fresh()),
            'message' => 'Поръчителят е обновен.',
        ]);
    }

    public function destroy(Request $request, int $leadId, int $guarantorId): JsonResponse
    {
        $lead = Lead::query()
            ->visibleToUser($request->user())
            ->findOrFail($leadId);

        $guarantor = $lead->guarantors()->findOrFail($guarantorId);
        $guarantor->delete();

        return response()->json(['message' => 'Поръчителят е изтрит.']);
    }

    public function statuses(): JsonResponse
    {
        $statuses = collect(LeadGuarantor::getStatusOptions())
            ->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
            ])
            ->values();

        return response()->json(['data' => $statuses]);
    }

    public function privacyConsent(
        Request $request,
        int $leadId,
        int $guarantorId,
        LeadPrivacyConsentPdfService $pdfService,
    ): StreamedResponse {
        $lead = Lead::query()
            ->visibleToUser($request->user())
            ->findOrFail($leadId);

        $guarantor = $lead->guarantors()->findOrFail($guarantorId);

        $document = $pdfService->buildGuarantorDownload($guarantor);

        return response()->streamDownload(
            static function () use ($document): void {
                echo $document['content'];
            },
            $document['download_name'],
            [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Content-Type' => 'application/pdf',
            ],
        );
    }

    private function format(LeadGuarantor $g): array
    {
        $documentNames = $g->getDocumentDisplayNames();

        return [
            'id' => $g->id,
            'lead_id' => $g->lead_id,
            'first_name' => $g->first_name,
            'middle_name' => $g->middle_name,
            'last_name' => $g->last_name,
            'egn' => $g->egn,
            'phone' => $g->phone,
            'email' => $g->email,
            'city' => $g->city,
            'workplace' => $g->workplace,
            'job_title' => $g->job_title,
            'salary' => $g->salary,
            'amount' => $g->amount,
            'marital_status' => $g->marital_status,
            'marital_status_label' => Lead::getMaritalStatusLabel($g->marital_status),
            'marital_status_note' => $g->marital_status_note,
            'children_under_18' => $g->children_under_18,
            'salary_bank' => $g->salary_bank,
            'credit_bank' => $g->credit_bank,
            'property_type' => $g->property_type,
            'property_location' => $g->property_location,
            'internal_notes' => $g->internal_notes,
            'documents' => collect($documentNames)->map(fn (string $name) => [
                'name' => $name,
            ])->values()->all(),
            'status' => $g->status,
            'status_label' => $g->status ? LeadGuarantor::getStatusLabel($g->status) : null,
            'status_options' => LeadGuarantor::getStatusOptions(),
        ];
    }
}

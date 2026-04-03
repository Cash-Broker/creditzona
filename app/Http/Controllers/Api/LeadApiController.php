<?php

namespace App\Http\Controllers\Api;

use App\Filament\Resources\Leads\LeadResource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UpdateLeadStatusRequest;
use App\Models\Lead;
use App\Models\LeadGuarantor;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadApiController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Lead::query()
            ->visibleToUser($user)
            ->with([
                'assignedUser:id,name',
                'additionalUser:id,name',
                'messages' => fn ($q) => $q->latest()->limit(1)->with('author:id,name'),
            ])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('assigned_user_id')) {
            $query->where('assigned_user_id', $request->integer('assigned_user_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('normalized_phone', 'like', "%{$search}%");
            });
        }

        $leads = $query->paginate(15);

        $leads->getCollection()->transform(fn (Lead $lead) => $this->formatLead($lead));

        return response()->json($leads);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $lead = Lead::query()
            ->visibleToUser($user)
            ->with([
                'assignedUser:id,name,email',
                'additionalUser:id,name,email',
                'returnedAdditionalUser:id,name',
                'guarantors',
                'messages.author:id,name',
            ])
            ->findOrFail($id);

        return response()->json([
            'data' => $this->formatLeadDetail($lead),
        ]);
    }

    public function updateStatus(UpdateLeadStatusRequest $request, int $id): JsonResponse
    {
        $user = $request->user();

        $lead = Lead::query()
            ->visibleToUser($user)
            ->findOrFail($id);

        $oldStatus = $lead->status;
        $lead->update(['status' => $request->validated('status')]);

        $lead = $lead->fresh(['assignedUser:id,name', 'additionalUser:id,name']);

        $this->notificationService->notifyStatusChanged($lead, $oldStatus, $lead->status);

        return response()->json([
            'data' => $this->formatLead($lead),
            'message' => 'Статусът е обновен успешно.',
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $lead = Lead::query()
            ->visibleToUser($user)
            ->findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'egn' => 'nullable|string|max:255',
            'phone' => 'sometimes|string|max:255',
            'email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:255',
            'workplace' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'salary' => 'nullable|integer|min:0',
            'marital_status' => 'nullable|string|in:single,married,divorced,widowed,cohabiting',
            'children_under_18' => 'nullable|integer|min:0',
            'salary_bank' => 'nullable|string|max:255',
            'credit_bank' => 'nullable|string|max:255',
            'amount' => 'sometimes|integer|min:0',
            'credit_type' => 'sometimes|string|in:consumer,mortgage,consumer_with_guarantor',
            'property_type' => 'nullable|string|max:255',
            'property_location' => 'nullable|string|max:255',
            'internal_notes' => 'nullable|string',
            'status' => 'sometimes|string',
            'additional_user_id' => 'nullable|integer|exists:users,id',
        ]);

        if (isset($validated['status']) && $validated['status'] !== $lead->status) {
            $oldStatus = $lead->status;
            $lead->update($validated);
            $this->notificationService->notifyStatusChanged($lead, $oldStatus, $lead->status);
        } else {
            $lead->update($validated);
        }

        $lead = $lead->fresh([
            'assignedUser:id,name,email',
            'additionalUser:id,name,email',
            'returnedAdditionalUser:id,name',
            'guarantors',
            'messages.author:id,name',
        ]);

        return response()->json([
            'data' => $this->formatLeadDetail($lead),
            'message' => 'Заявката е обновена успешно.',
        ]);
    }

    public function markForLater(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $lead = Lead::query()
            ->visibleToUser($user)
            ->findOrFail($id);

        $lead->update([
            'marked_for_later_at' => $lead->marked_for_later_at ? null : now(),
        ]);

        $lead = $lead->fresh([
            'assignedUser:id,name,email',
            'additionalUser:id,name,email',
            'returnedAdditionalUser:id,name',
            'guarantors',
            'messages.author:id,name',
        ]);

        return response()->json([
            'data' => $this->formatLeadDetail($lead),
            'message' => $lead->isMarkedForLater() ? 'Маркирано за по-късно.' : 'Премахнато от по-късно.',
        ]);
    }

    public function privacyConsent(Request $request, int $id): StreamedResponse|JsonResponse
    {
        $lead = Lead::query()
            ->visibleToUser($request->user())
            ->findOrFail($id);

        $document = $lead->findPrivacyConsentDocumentDownload();

        if (!$document || !$document['is_available']) {
            return response()->json(['message' => 'Декларацията не е налична.'], 404);
        }

        return Storage::disk('local')->download(
            $document['path'],
            $document['download_name'] ?? $document['name'],
            [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Content-Type' => 'application/pdf',
            ],
        );
    }

    public function statuses(): JsonResponse
    {
        $statuses = collect(LeadResource::getStatusOptions())
            ->map(fn (string $label, string $value) => [
                'value' => $value,
                'label' => $label,
            ])
            ->values();

        return response()->json(['data' => $statuses]);
    }

    private function formatLead(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'first_name' => $lead->first_name,
            'middle_name' => $lead->middle_name,
            'last_name' => $lead->last_name,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'city' => $lead->city,
            'status' => $lead->status,
            'status_label' => LeadResource::getStatusLabel($lead->status),
            'credit_type' => $lead->credit_type,
            'credit_type_label' => Lead::getCreditTypeLabel($lead->credit_type),
            'amount' => $lead->amount,
            'assigned_user' => $lead->assignedUser ? [
                'id' => $lead->assignedUser->id,
                'name' => $lead->assignedUser->name,
            ] : null,
            'additional_user' => $lead->additionalUser ? [
                'id' => $lead->additionalUser->id,
                'name' => $lead->additionalUser->name,
            ] : null,
            'marked_for_later' => $lead->isMarkedForLater(),
            'created_at' => $lead->created_at->toIso8601String(),
            'updated_at' => $lead->updated_at->toIso8601String(),
            'last_message' => $lead->relationLoaded('messages') && $lead->messages->isNotEmpty() ? [
                'body' => mb_substr($lead->messages->first()->body, 0, 50),
                'author' => $lead->messages->first()->author?->name,
                'created_at' => $lead->messages->first()->created_at->toIso8601String(),
            ] : null,
        ];
    }

    private function formatLeadDetail(Lead $lead): array
    {
        $documentNames = $lead->getDocumentDisplayNames();

        return array_merge($this->formatLead($lead), [
            'egn' => $lead->egn,
            'workplace' => $lead->workplace,
            'job_title' => $lead->job_title,
            'salary' => $lead->salary,
            'marital_status' => $lead->marital_status,
            'marital_status_label' => Lead::getMaritalStatusLabel($lead->marital_status),
            'children_under_18' => $lead->children_under_18,
            'salary_bank' => $lead->salary_bank,
            'credit_bank' => $lead->credit_bank,
            'property_type' => $lead->property_type,
            'property_location' => $lead->property_location,
            'internal_notes' => $lead->internal_notes,
            'source' => $lead->source,
            'privacy_consent_accepted' => (bool) $lead->privacy_consent_accepted,
            'privacy_consent_accepted_at' => $lead->privacy_consent_accepted_at?->toIso8601String(),
            'documents' => collect($documentNames)->map(fn (string $name) => [
                'name' => $name,
            ])->values()->all(),
            'returned_additional_user' => $lead->returnedAdditionalUser ? [
                'id' => $lead->returnedAdditionalUser->id,
                'name' => $lead->returnedAdditionalUser->name,
            ] : null,
            'credit_type_options' => Lead::getCreditTypeOptions(),
            'marital_status_options' => Lead::getMaritalStatusOptions(),
            'guarantors' => $lead->guarantors->map(fn ($g) => [
                'id' => $g->id,
                'first_name' => $g->first_name,
                'middle_name' => $g->middle_name,
                'last_name' => $g->last_name,
                'phone' => $g->phone,
                'email' => $g->email,
                'city' => $g->city,
                'egn' => $g->egn,
                'status' => $g->status,
                'status_label' => $g->status ? LeadGuarantor::getStatusLabel($g->status) : null,
                'amount' => $g->amount,
            ])->all(),
            'messages' => $lead->messages->map(fn ($m) => [
                'id' => $m->id,
                'body' => $m->body,
                'author' => $m->author ? ['id' => $m->author->id, 'name' => $m->author->name] : null,
                'created_at' => $m->created_at->toIso8601String(),
            ])->all(),
        ]);
    }
}

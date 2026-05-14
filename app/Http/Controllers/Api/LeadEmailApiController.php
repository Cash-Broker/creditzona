<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadEmail;
use App\Services\LeadService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadEmailApiController extends Controller
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    public function index(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $lead = Lead::query()
            ->visibleToUser($user)
            ->findOrFail($id);

        $emails = $lead->emails()
            ->with('sender:id,name')
            ->orderByDesc('sent_at')
            ->get()
            ->map(fn (LeadEmail $email) => $this->formatEmail($email));

        return response()->json(['data' => $emails]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $lead = Lead::query()
            ->visibleToUser($user)
            ->findOrFail($id);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:10000'],
        ]);

        try {
            $email = $this->leadService->sendEmailToLead($lead, $user, $validated['body']);
        } catch (AuthorizationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $email->load('sender:id,name');

        return response()->json([
            'data' => $this->formatEmail($email),
            'message' => 'Имейлът е изпратен.',
        ], 201);
    }

    private function formatEmail(LeadEmail $email): array
    {
        return [
            'id' => $email->id,
            'body' => $email->body,
            'subject' => $email->subject,
            'from_email' => $email->from_email,
            'to_email' => $email->to_email,
            'sender' => $email->sender ? [
                'id' => $email->sender->id,
                'name' => $email->sender->name,
            ] : null,
            'sent_at' => $email->sent_at?->toIso8601String(),
        ];
    }
}

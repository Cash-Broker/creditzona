<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLeadMessageRequest;
use App\Models\Lead;
use App\Services\LeadMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadMessageApiController extends Controller
{
    public function __construct(
        private readonly LeadMessageService $leadMessageService,
    ) {}

    public function index(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $lead = Lead::query()
            ->visibleToUser($user)
            ->findOrFail($id);

        $query = $lead->messages()
            ->with('author:id,name')
            ->orderBy('created_at');

        if ($request->has('guarantor_id')) {
            $query->where('guarantor_id', $request->input('guarantor_id'));
        } else {
            $query->whereNull('guarantor_id');
        }

        $messages = $query->get()->map(fn ($message) => $this->formatMessage($message));

        return response()->json(['data' => $messages]);
    }

    public function store(StoreLeadMessageRequest $request, int $id): JsonResponse
    {
        $user = $request->user();

        $lead = Lead::query()
            ->visibleToUser($user)
            ->findOrFail($id);

        $guarantorId = $request->input('guarantor_id');

        if ($guarantorId) {
            $lead->guarantors()->findOrFail($guarantorId);
        }

        $message = $lead->messages()->create([
            'user_id' => $user->id,
            'guarantor_id' => $guarantorId,
            'body' => $request->validated('body'),
        ])->loadMissing('author');

        return response()->json([
            'data' => $this->formatMessage($message),
            'message' => 'Съобщението е изпратено успешно.',
        ], 201);
    }

    public function update(Request $request, int $id, int $messageId): JsonResponse
    {
        $user = $request->user();

        $lead = Lead::query()
            ->visibleToUser($user)
            ->findOrFail($id);

        $message = $lead->messages()->findOrFail($messageId);

        if ($message->user_id !== $user->id) {
            return response()->json(['message' => 'Нямаш право да редактираш това съобщение.'], 403);
        }

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $message->update($validated);

        return response()->json([
            'data' => $this->formatMessage($message->fresh('author:id,name')),
            'message' => 'Съобщението е редактирано.',
        ]);
    }

    public function destroy(Request $request, int $id, int $messageId): JsonResponse
    {
        $user = $request->user();

        $lead = Lead::query()
            ->visibleToUser($user)
            ->findOrFail($id);

        $message = $lead->messages()->findOrFail($messageId);

        if ($message->user_id !== $user->id) {
            return response()->json(['message' => 'Нямаш право да триеш това съобщение.'], 403);
        }

        $message->delete();

        return response()->json(['message' => 'Съобщението е изтрито.']);
    }

    private function formatMessage($message): array
    {
        $isEdited = $message->updated_at && $message->updated_at->gt($message->created_at->addSeconds(1));

        return [
            'id' => $message->id,
            'body' => $message->body,
            'guarantor_id' => $message->guarantor_id,
            'author' => $message->author ? [
                'id' => $message->author->id,
                'name' => $message->author->name,
            ] : null,
            'created_at' => $message->created_at->toIso8601String(),
            'updated_at' => $message->updated_at->toIso8601String(),
            'is_edited' => (bool) $isEdited,
        ];
    }
}

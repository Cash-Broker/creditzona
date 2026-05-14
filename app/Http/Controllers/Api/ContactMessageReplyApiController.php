<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\ContactMessageReply;
use App\Services\ContactMessageService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactMessageReplyApiController extends Controller
{
    public function __construct(
        private readonly ContactMessageService $contactMessageService,
    ) {}

    public function index(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $query = ContactMessage::query();

        if (! $user->isAdmin()) {
            $query->where('assigned_user_id', $user->id);
        }

        $message = $query->findOrFail($id);

        $replies = $message->replies()
            ->with('sender:id,name')
            ->orderBy('sent_at')
            ->get()
            ->map(fn (ContactMessageReply $reply) => $this->formatReply($reply));

        return response()->json(['data' => $replies]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $message = ContactMessage::query()->findOrFail($id);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $reply = $this->contactMessageService->reply($message, $user, $validated['body']);
        } catch (AuthorizationException $exception) {
            return response()->json(['message' => $exception->getMessage()], 403);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $reply->load('sender:id,name');

        return response()->json([
            'data' => $this->formatReply($reply),
            'message' => 'Отговорът е изпратен.',
        ], 201);
    }

    private function formatReply(ContactMessageReply $reply): array
    {
        return [
            'id' => $reply->id,
            'body' => $reply->body,
            'subject' => $reply->subject,
            'from_email' => $reply->from_email,
            'to_email' => $reply->to_email,
            'sender' => $reply->sender ? [
                'id' => $reply->sender->id,
                'name' => $reply->sender->name,
            ] : null,
            'sent_at' => $reply->sent_at?->toIso8601String(),
        ];
    }
}

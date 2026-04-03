<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AssignContactMessageRequest;
use App\Models\ContactMessage;
use App\Models\User;
use App\Services\ContactMessageService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactMessageApiController extends Controller
{
    public function __construct(
        private readonly ContactMessageService $contactMessageService,
        private readonly NotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = ContactMessage::query()
            ->with('assignedUser:id,name')
            ->orderByDesc('created_at');

        if ($user->isAdmin() && $request->boolean('archived')) {
            $query->adminArchived();
        } elseif ($user->isAdmin()) {
            $query->adminActive();
        } else {
            $query->active()->where('assigned_user_id', $user->id);
        }

        $messages = $query->paginate(15);

        $messages->getCollection()->transform(
            fn (ContactMessage $message) => $this->formatMessage($message),
        );

        return response()->json($messages);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $query = ContactMessage::query()->with('assignedUser:id,name');

        if (! $user->isAdmin()) {
            $query->where('assigned_user_id', $user->id);
        }

        $message = $query->findOrFail($id);

        return response()->json(['data' => $this->formatMessage($message)]);
    }

    public function assign(AssignContactMessageRequest $request, int $id): JsonResponse
    {
        $contactMessage = ContactMessage::query()->findOrFail($id);
        $operator = User::query()->findOrFail($request->validated('user_id'));

        $contactMessage = $this->contactMessageService->assignToOperator(
            $contactMessage,
            $operator,
            $request->user(),
        );

        $this->notificationService->notifyNewContactMessage($contactMessage);

        return response()->json([
            'data' => $this->formatMessage($contactMessage),
            'message' => 'Съобщението е закачено успешно.',
        ]);
    }

    public function archive(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $query = ContactMessage::query();

        if (! $user->isAdmin()) {
            $query->where('assigned_user_id', $user->id);
        }

        $contactMessage = $query->findOrFail($id);

        $contactMessage = $this->contactMessageService->archiveMessage($contactMessage, $user);

        return response()->json([
            'data' => $this->formatMessage($contactMessage),
            'message' => 'Съобщението е архивирано успешно.',
        ]);
    }

    private function formatMessage(ContactMessage $message): array
    {
        return [
            'id' => $message->id,
            'full_name' => $message->full_name,
            'phone' => $message->phone,
            'email' => $message->email,
            'message' => $message->message,
            'assigned_user' => $message->assignedUser ? [
                'id' => $message->assignedUser->id,
                'name' => $message->assignedUser->name,
            ] : null,
            'archived_at' => $message->archived_at?->toIso8601String(),
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }
}

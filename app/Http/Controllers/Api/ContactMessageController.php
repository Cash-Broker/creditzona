<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Services\ContactMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ContactMessageController extends Controller
{
    public function __construct(
        private readonly ContactMessageService $contactMessageService,
    ) {}

    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        try {
            $this->contactMessageService->storeMessage($request->validated());
        } catch (\Throwable $exception) {
            Log::error('Failed to store contact message.', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Възникна проблем при изпращането на съобщението. Моля, опитайте отново след малко.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Съобщението беше изпратено успешно.',
        ], 201);
    }
}

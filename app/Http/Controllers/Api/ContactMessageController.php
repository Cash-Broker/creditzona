<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;

class ContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        ContactMessage::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Съобщението беше изпратено успешно.',
        ], 201);
    }
}

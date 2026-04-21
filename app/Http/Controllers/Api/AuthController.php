<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Грешен имейл или парола.',
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Излязохте успешно.']);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_admin' => $user->isAdmin(),
            ],
        ]);
    }

    public function toggleAvailability(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'is_available_for_lead_assignment' => !$user->is_available_for_lead_assignment,
        ]);

        return response()->json([
            'data' => [
                'is_available' => (bool) $user->is_available_for_lead_assignment,
            ],
            'message' => $user->is_available_for_lead_assignment ? 'Наличен за заявки.' : 'Недостъпен за заявки.',
        ]);
    }

    public function savePushToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $request->user()->update([
            'expo_push_token' => $request->input('token'),
        ]);

        return response()->json(['message' => 'Токенът е запазен.']);
    }
}

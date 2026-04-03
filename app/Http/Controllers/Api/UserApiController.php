<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => $this->formatUser($user));

        return response()->json(['data' => $users]);
    }

    public function online(Request $request): JsonResponse
    {
        $users = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_OPERATOR])
            ->where('is_available_for_lead_assignment', true)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => $this->formatUser($user));

        return response()->json(['data' => $users]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_available' => (bool) $user->is_available_for_lead_assignment,
        ];
    }
}

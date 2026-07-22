<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'device_name' => ['sometimes', 'string', 'max:255'],
            'staff_id' => ['nullable', 'string', 'max:255', 'unique:users,staff_id'],
            'position' => ['nullable', 'string', 'max:255'],
            'group' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'staff_id' => $validated['staff_id'] ?? null,
            'position' => $validated['position'] ?? null,
            'group' => $validated['group'] ?? null,
        ]);

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $user->createApiToken($validated['device_name'] ?? 'api'),
            ],
        ], Response::HTTP_CREATED);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $user->createApiToken($validated['device_name'] ?? 'api'),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $request->user(),
        ]);
    }

    public function logout(Request $request): Response
    {
        $currentToken = $request->user()->getRelation('currentApiToken');

        if ($currentToken instanceof ApiToken) {
            $currentToken->delete();

            return response()->noContent();
        }

        $plainToken = $request->bearerToken();

        if ($plainToken !== null && $plainToken !== '') {
            ApiToken::query()
                ->where('user_id', $request->user()->id)
                ->where('token_hash', hash('sha256', $plainToken))
                ->delete();
        }

        return response()->noContent();
    }
}

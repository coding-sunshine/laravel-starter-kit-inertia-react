<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSessionRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

final class AuthController extends Controller
{
    public function login(CreateSessionRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->validateCredentials();

        if ($user->two_factor_confirmed_at !== null) {
            return response()->json([
                'error' => [
                    'code' => 'two_factor_required',
                    'message' => 'Two-factor authentication is enabled. Please log in via the web flow.',
                ],
            ], 422);
        }

        $accessTokenTtlMinutes = 15;
        $refreshTokenTtlDays = 30;

        $accessToken = $user->createTokenWithPermissionAbilities(
            'mobile-access',
            now()->addMinutes($accessTokenTtlMinutes),
        );

        $refreshToken = $user->createToken(
            'mobile-refresh',
            ['refresh-access-token'],
            now()->addDays($refreshTokenTtlDays),
        );

        /** @var Role|null $primaryRole */
        $primaryRole = $user->roles()->select('id', 'name')->first();

        $roles = $user->roles()->select('id', 'name')->get();

        return response()->json([
            'data' => [
                'access_token' => $accessToken->plainTextToken,
                'refresh_token' => $refreshToken->plainTextToken,
                'token_type' => 'Bearer',
                'expires_in' => $accessTokenTtlMinutes * 60,
                'refresh_expires_in' => $refreshTokenTtlDays * 24 * 60 * 60,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $primaryRole !== null ? [
                        'id' => $primaryRole->id,
                        'name' => $primaryRole->name,
                    ] : null,
                    'roles' => $roles,
                ],
            ],
            'message' => 'Login successful.',
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(
                [
                    'error' => [
                        'code' => 'unauthenticated',
                        'message' => 'Authentication required.',
                    ],
                ],
                401,
            );
        }

        $currentToken = $user->currentAccessToken();

        if ($currentToken === null || ! $currentToken->can('refresh-access-token')) {
            return response()->json(
                [
                    'error' => [
                        'code' => 'invalid_refresh_token',
                        'message' => 'The provided token cannot be used to refresh access.',
                    ],
                ],
                403,
            );
        }

        $accessTokenTtlMinutes = 15;

        $newAccessToken = $user->createTokenWithPermissionAbilities(
            'mobile-access',
            now()->addMinutes($accessTokenTtlMinutes),
        );

        return response()->json([
            'data' => [
                'access_token' => $newAccessToken->plainTextToken,
                'token_type' => 'Bearer',
                'expires_in' => $accessTokenTtlMinutes * 60,
            ],
            'message' => 'Access token refreshed.',
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            return response()->json(
                [
                    'error' => [
                        'code' => 'unauthenticated',
                        'message' => 'Authentication required.',
                    ],
                ],
                401,
            );
        }

        $currentToken = $user->currentAccessToken();

        if ($currentToken !== null) {
            $currentToken->delete();
        }

        $user->tokens()
            ->whereIn('name', ['mobile-access', 'mobile-refresh'])
            ->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}

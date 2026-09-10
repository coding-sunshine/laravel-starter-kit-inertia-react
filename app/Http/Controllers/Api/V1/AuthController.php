<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateApiUser;
use App\Actions\DeleteUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DeleteMeRequest;
use App\Http\Requests\Api\V1\RegisterApiUserRequest;
use App\Http\Requests\CreateSessionRequest;
use App\Models\Siding;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        $primarySiding = $user->getPrimarySiding();

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
                    'siding_id' => $primarySiding?->id,
                    'siding' => $primarySiding !== null ? [
                        'id' => $primarySiding->id,
                        'name' => $primarySiding->name,
                        'code' => $primarySiding->code,
                    ] : null,
                    'sidings' => $this->sidingsArrayForResponse($user),
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

    public function register(RegisterApiUserRequest $request, CreateApiUser $action): JsonResponse
    {
        /** @var array<string, mixed> $validated */
        $validated = $request->validated();
        $password = (string) $validated['password'];
        unset($validated['password'], $validated['password_confirmation']);

        $user = $action->handle($validated, $password);

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
        $primarySiding = $user->getPrimarySiding();

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
                    'siding_id' => $primarySiding?->id,
                    'siding' => $primarySiding !== null ? [
                        'id' => $primarySiding->id,
                        'name' => $primarySiding->name,
                        'code' => $primarySiding->code,
                    ] : null,
                    'sidings' => $this->sidingsArrayForResponse($user),
                    'role' => $primaryRole !== null ? [
                        'id' => $primaryRole->id,
                        'name' => $primaryRole->name,
                    ] : null,
                    'roles' => $roles,
                ],
            ],
            'message' => 'Registration successful.',
        ]);
    }

    public function deleteMe(DeleteMeRequest $request, DeleteUser $action): Response
    {
        /** @var User $user */
        $user = $request->user();

        $currentToken = $user->currentAccessToken();
        if ($currentToken !== null) {
            $currentToken->delete();
        }

        $user->tokens()
            ->whereIn('name', ['mobile-access', 'mobile-refresh'])
            ->delete();

        $action->handle($user);

        return response()->noContent();
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

    /**
     * All sidings the user may access (matches API indent scoping: super-admin sees all; others see pivot assignments and legacy users.siding_id).
     *
     * @return list<array{id: int, name: string, code: string|null, station_code: string|null, is_primary: bool}>
     */
    private function sidingsArrayForResponse(User $user): array
    {
        $primaryId = $user->getPrimarySiding()?->id;

        if ($user->isSuperAdmin()) {
            return Siding::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'station_code'])
                ->map(static fn (Siding $siding): array => [
                    'id' => $siding->id,
                    'name' => $siding->name,
                    'code' => $siding->code,
                    'station_code' => $siding->station_code,
                    'is_primary' => $primaryId !== null && (int) $siding->id === (int) $primaryId,
                ])
                ->values()
                ->all();
        }

        $sidings = $user->sidings()
            ->orderBy('sidings.name')
            ->get(['sidings.id', 'sidings.name', 'sidings.code', 'sidings.station_code']);

        if ($sidings->isEmpty() && $user->siding_id !== null) {
            $fallback = Siding::query()->find($user->siding_id);
            if ($fallback !== null) {
                return [[
                    'id' => $fallback->id,
                    'name' => $fallback->name,
                    'code' => $fallback->code,
                    'station_code' => $fallback->station_code,
                    'is_primary' => true,
                ]];
            }
        }

        return $sidings
            ->map(function (Siding $siding) use ($primaryId): array {
                $pivotPrimary = (bool) ($siding->pivot->is_primary ?? false);

                return [
                    'id' => $siding->id,
                    'name' => $siding->name,
                    'code' => $siding->code,
                    'station_code' => $siding->station_code,
                    'is_primary' => $pivotPrimary || ($primaryId !== null && (int) $siding->id === (int) $primaryId),
                ];
            })
            ->values()
            ->all();
    }
}

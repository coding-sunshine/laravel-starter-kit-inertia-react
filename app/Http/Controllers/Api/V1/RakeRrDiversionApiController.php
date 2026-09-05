<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DiverrtDestination;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\RakeRrHubPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RakeRrDiversionApiController extends Controller
{
    public function updateDiversionMode(Request $request, Rake $rake): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->assertCanAccessRake($user, $rake);
        abort_unless($this->hasSectionPermission($user, 'sections.rakes.update'), 403);

        $validated = $request->validate([
            'is_diverted' => ['required', 'boolean'],
        ]);

        $toDiverted = (bool) $validated['is_diverted'];

        if (! $toDiverted) {
            if ($rake->rrDocuments()->whereNotNull('diverrt_destination_id')->exists()) {
                $message = 'Remove diversion Railway Receipts before turning off diverted mode.';

                return response()->json([
                    'message' => $message,
                    'errors' => ['is_diverted' => [$message]],
                ], 422);
            }
        }

        $rake->update([
            'is_diverted' => $toDiverted,
            'updated_by' => $user->id,
        ]);

        $rake->refresh()->load(['rrDocuments', 'diverrtDestinations']);

        return response()->json([
            'message' => $toDiverted
                ? 'Diverted mode enabled for this rake.'
                : 'Diverted mode disabled for this rake.',
            'rr_hub' => RakeRrHubPayload::fromRake($rake),
        ]);
    }

    public function storeDiverrtDestination(Request $request, Rake $rake): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->assertCanAccessRake($user, $rake);
        abort_unless($this->hasSectionPermission($user, 'sections.rakes.update'), 403);

        $validated = $request->validate([
            'location' => ['required', 'string', 'max:255'],
        ]);

        $rake->diverrtDestinations()->create([
            'location' => mb_trim($validated['location']),
            'data_source' => 'manual',
        ]);

        $rake->refresh()->load(['rrDocuments', 'diverrtDestinations']);

        return response()->json([
            'message' => 'Diversion destination added.',
            'rr_hub' => RakeRrHubPayload::fromRake($rake),
        ], 201);
    }

    public function destroyDiverrtDestination(Request $request, Rake $rake, DiverrtDestination $diverrtDestination): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->assertCanAccessRake($user, $rake);
        abort_unless($this->hasSectionPermission($user, 'sections.rakes.update'), 403);

        if ((int) $diverrtDestination->rake_id !== (int) $rake->id) {
            abort(404);
        }

        if ($diverrtDestination->rrDocuments()->exists()) {
            $message = 'Cannot delete a diversion destination that already has a Railway Receipt.';

            return response()->json([
                'message' => $message,
                'errors' => ['diverrt_destination' => [$message]],
            ], 422);
        }

        $diverrtDestination->delete();

        $rake->refresh()->load(['rrDocuments', 'diverrtDestinations']);

        return response()->json([
            'message' => 'Diversion destination removed.',
            'rr_hub' => RakeRrHubPayload::fromRake($rake),
        ]);
    }

    private function assertCanAccessRake(User $user, Rake $rake): void
    {
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        abort_unless(in_array($rake->siding_id, $sidingIds, true), 403);
    }

    private function hasSectionPermission(User $user, string $permission): bool
    {
        if ($user->can('bypass-permissions')) {
            return true;
        }

        if (TenantContext::check() && $user->canInCurrentOrganization($permission)) {
            return true;
        }

        return $user->hasPermissionTo($permission);
    }
}

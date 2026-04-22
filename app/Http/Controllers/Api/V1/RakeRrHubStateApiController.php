<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\User;
use App\Services\TenantContext;
use App\Support\RakeRrHubPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RakeRrHubStateApiController extends Controller
{
    public function __invoke(Request $request, Rake $rake): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        abort_unless($this->hasSectionPermission($user, 'sections.railway_receipts.view'), 403);

        if (! $user->isSuperAdmin() && ! $user->canAccessSiding((int) $rake->siding_id)) {
            abort(403);
        }

        $rake->load(['rrDocuments', 'diverrtDestinations']);

        return response()->json([
            'rr_hub' => RakeRrHubPayload::fromRake($rake),
        ]);
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

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Rake;
use App\Services\LiveMonitor\LiveMonitorDataBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Control Room (Live Monitor) — animated, near-realtime view of rake loading
 * across visible sidings, with click-through to a single-rake deep view.
 *
 * Authorization is enforced by AutoPermissionMiddleware against the
 * sections.live_monitor.view permission (mapped in config/section_permissions.php).
 */
final class LiveMonitorController extends Controller
{
    public function __construct(private readonly LiveMonitorDataBuilder $builder) {}

    /**
     * Multi-siding overview.
     */
    public function index(Request $request): Response
    {
        $payload = $this->builder->forOverview($request->user());

        $sidingIds = array_map(
            static fn (array $s): int => (int) $s['siding_id'],
            $payload['sidings'],
        );

        return Inertia::render('control-room/index', [
            'overview' => $payload,
            'subscribable_sidings' => $sidingIds,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Single-rake deep view.
     */
    public function show(Request $request, Rake $rake): Response
    {
        $rake->loadMissing('siding');

        $user = $request->user();
        if ($rake->siding_id !== null && ! $this->userCanSeeSiding($user, (int) $rake->siding_id)) {
            throw new AccessDeniedHttpException('You do not have access to this siding.');
        }

        return Inertia::render('control-room/show', [
            'rakeData' => $this->builder->forRake($rake),
            'subscribable_sidings' => $rake->siding_id !== null ? [(int) $rake->siding_id] : [],
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function userCanSeeSiding(?\App\Models\User $user, int $sidingId): bool
    {
        if ($user === null) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        return $user->sidings()->where('sidings.id', $sidingId)->exists()
            || (int) ($user->siding_id ?? 0) === $sidingId;
    }
}

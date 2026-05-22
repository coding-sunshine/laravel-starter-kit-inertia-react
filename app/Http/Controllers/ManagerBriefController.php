<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTransferObjects\ManagerBrief\Payload;
use App\Models\Siding;
use App\Models\User;
use App\Services\ManagerBrief\LiveExposureCalculator;
use App\Services\ManagerBrief\OperatorScoreboard;
use App\Services\ManagerBrief\PendingQueue;
use App\Services\ManagerBrief\TrendStrip;
use App\Services\SidingContext;
use App\Services\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

final class ManagerBriefController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($this->hasSectionPermission($user, 'sections.manager_brief.view'), 403);

        $siding = $this->resolveSiding($user);
        $sidingId = $siding->id;

        // ---------------------------------------------------------------------------
        // AI Brief — read from cache, dispatch refresh when missing or stale
        // ---------------------------------------------------------------------------
        $cacheKey = "manager-brief:{$sidingId}:v1";
        $cached = Cache::get($cacheKey);

        if ($cached === null) {
            // Cache miss → serve placeholder, queue async generation (deduplicated).
            $this->dispatchRefreshOnce($sidingId);

            $actions = [];
            $generatedAt = CarbonImmutable::now()->toIso8601String();
            $aiStatus = 'pending';
            $modelUsed = null;
        } else {
            // Cache hit → check for staleness (> 60 minutes)
            $payload = Payload::fromArray($cached);
            $generatedAt = $payload->generatedAt->toIso8601String();
            $aiStatus = $payload->aiStatus;
            $modelUsed = $payload->modelUsed;
            $actions = array_map(
                static fn ($card): array => $card->toArray(),
                $payload->actions,
            );

            $ageMinutes = $payload->generatedAt->diffInMinutes(CarbonImmutable::now());
            if ($ageMinutes > 60) {
                // Stale — serve current data and queue a background refresh (deduplicated).
                $this->dispatchRefreshOnce($sidingId);
            }
        }

        // ---------------------------------------------------------------------------
        // Live widgets — always fresh, each widget isolated in try/catch
        // ---------------------------------------------------------------------------
        $widgetErrors = [];

        $liveExposure = null;

        try {
            $liveExposure = app(LiveExposureCalculator::class)->handle($sidingId);
        } catch (Throwable $e) {
            Log::warning('manager-brief: widget failed', [
                'widget' => 'live_exposure',
                'siding_id' => $sidingId,
                'error' => $e->getMessage(),
            ]);
            $widgetErrors[] = 'live_exposure';
        }

        $operatorScoreboard = null;

        try {
            $operatorScoreboard = app(OperatorScoreboard::class)->handle($sidingId);
        } catch (Throwable $e) {
            Log::warning('manager-brief: widget failed', [
                'widget' => 'operator_scoreboard',
                'siding_id' => $sidingId,
                'error' => $e->getMessage(),
            ]);
            $widgetErrors[] = 'operator_scoreboard';
        }

        $pendingQueue = null;

        try {
            $pendingQueue = app(PendingQueue::class)->handle($sidingId);
        } catch (Throwable $e) {
            Log::warning('manager-brief: widget failed', [
                'widget' => 'pending_queue',
                'siding_id' => $sidingId,
                'error' => $e->getMessage(),
            ]);
            $widgetErrors[] = 'pending_queue';
        }

        $trendStrip = null;

        try {
            $trendStrip = app(TrendStrip::class)->handle($sidingId);
        } catch (Throwable $e) {
            Log::warning('manager-brief: widget failed', [
                'widget' => 'trend_strip',
                'siding_id' => $sidingId,
                'error' => $e->getMessage(),
            ]);
            $widgetErrors[] = 'trend_strip';
        }

        return Inertia::render('manager-brief/index', [
            'actions' => $actions,
            'generated_at' => $generatedAt,
            'ai_status' => $aiStatus,
            'model_used' => $modelUsed,
            'live_exposure' => $liveExposure,
            'operator_scoreboard' => $operatorScoreboard,
            'pending_queue' => $pendingQueue,
            'trend_strip' => $trendStrip,
            'widget_errors' => $widgetErrors,
            'can_refresh' => $this->hasSectionPermission($user, 'sections.manager_brief.refresh'),
            'siding' => [
                'id' => $siding->id,
                'name' => $siding->name,
                'code' => $siding->code,
            ],
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($this->hasSectionPermission($user, 'sections.manager_brief.refresh'), 403);

        $siding = $this->resolveSiding($user);
        $sidingId = $siding->id;

        $this->dispatchRefreshOnce($sidingId);

        return response()->json(['dispatched' => true], 202);
    }

    /**
     * Dispatch the manager-brief refresh command at most once per 5-minute window.
     *
     * Concurrent HTTP requests (e.g. multiple polling tabs) and stale-cache paths
     * can all race to enqueue the same expensive AI job. We use an atomic lock with
     * a 5-minute TTL to ensure only the first requester within that window dispatches;
     * subsequent callers cannot acquire the lock and skip dispatch.
     *
     * The lock is held for the full TTL (300 s) rather than released after dispatch.
     * This prevents re-dispatch within the window even from sequential requests.
     * The schedule's withoutOverlapping() guards against duplicate cron runs;
     * this lock only deduplicates the HTTP-triggered dispatches.
     */
    private function dispatchRefreshOnce(int $sidingId): void
    {
        $lock = Cache::lock("manager-brief:refresh-dispatch:{$sidingId}", 300);

        if (! $lock->get()) {
            // Another request already holds the dispatch lock within this 5-minute window; skip.
            return;
        }

        // Lock acquired — dispatch and leave the lock held for its TTL so that
        // further requests within the window are deduplicated.
        Artisan::queue('manager-brief:refresh', ['--siding' => $sidingId]);
    }

    /**
     * Resolve the active siding for the current user.
     *
     * For super-admins with no siding selected ("All sidings"), we fall back to
     * the first siding in the database, as multi-siding aggregation is a phase-2
     * improvement.
     *
     * TODO(manager-brief): aggregate across allowed sidings for super-admins in
     * "All sidings" mode rather than falling back to the first siding.
     */
    private function resolveSiding(User $user): Siding
    {
        $siding = SidingContext::get();

        if ($siding instanceof Siding) {
            return $siding;
        }

        // Super-admin without explicit siding selection: pick the first siding that belongs
        // to the current tenant organisation (when in tenant context) to prevent data leakage
        // across organisations. Phase-2 will aggregate across all accessible sidings.
        if ($user->isSuperAdmin()) {
            $superAdminQuery = Siding::query()->orderBy('id');

            if (TenantContext::check()) {
                $superAdminQuery->where('organization_id', TenantContext::id());
            }

            $first = $superAdminQuery->first();

            if ($first instanceof Siding) {
                return $first;
            }

            abort(404, 'No siding available for manager brief in the current organisation.');
        }

        // Siding-scoped user: use their primary siding or first assigned siding.
        $primary = $user->getPrimarySiding();
        if ($primary instanceof Siding) {
            return $primary;
        }

        $assigned = $user->sidings()->orderBy('sidings.id')->first();
        if ($assigned instanceof Siding) {
            return $assigned;
        }

        // Last resort: first siding in DB
        $first = Siding::query()->orderBy('id')->first();
        if ($first instanceof Siding) {
            return $first;
        }

        abort(404, 'No siding available for manager brief.');
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

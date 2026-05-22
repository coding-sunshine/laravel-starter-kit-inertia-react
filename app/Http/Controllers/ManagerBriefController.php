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
            // Cache miss → serve placeholder, queue async generation
            Artisan::queue('manager-brief:refresh', ['--siding' => $sidingId]);

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
                // Stale — serve current data and queue a background refresh
                Artisan::queue('manager-brief:refresh', ['--siding' => $sidingId]);
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

        Artisan::queue('manager-brief:refresh', ['--siding' => $sidingId]);

        return response()->json(['dispatched' => true], 202);
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

        // Super-admin without explicit siding selection: pick the first allowed siding.
        // Phase-2 will aggregate across all accessible sidings.
        if ($user->isSuperAdmin()) {
            $first = Siding::query()->orderBy('id')->first();
            if ($first instanceof Siding) {
                return $first;
            }
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

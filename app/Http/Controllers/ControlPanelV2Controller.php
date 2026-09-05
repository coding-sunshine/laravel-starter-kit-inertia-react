<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Rake;
use App\Models\Siding;
use App\Models\Wagon;
use App\Services\LiveMonitor\LiveMonitorDataBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Control Panel v2 — animated, real-time multi-siding + single-rake views.
 *
 * Parallel build alongside LiveMonitorController. Reads from the same
 * LiveMonitorDataBuilder service (read-only) so old /control-room remains
 * unaffected. Routed by Siding (not Rake) so the URL stays stable across
 * rake placements at the same siding.
 */
final class ControlPanelV2Controller extends Controller
{
    public function __construct(private readonly LiveMonitorDataBuilder $builder) {}

    public function index(Request $request): Response
    {
        $payload = $this->builder->forOverview($request->user());

        $sidingIds = array_map(
            static fn (array $s): int => (int) $s['siding_id'],
            $payload['sidings'],
        );

        return Inertia::render('control-panel-v2/index', [
            'overview' => $payload,
            'subscribable_sidings' => $sidingIds,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function show(Request $request, Siding $siding): Response
    {
        $user = $request->user();

        if (! $this->userCanSeeSiding($user, (int) $siding->id)) {
            throw new AccessDeniedHttpException('You do not have access to this siding.');
        }

        $rake = $this->activeRakeForSiding($siding);

        $rakeData = $rake !== null
            ? $this->builder->forRake($rake)
            : null;

        return Inertia::render('control-panel-v2/siding', [
            'siding' => [
                'id' => (int) $siding->id,
                'name' => $siding->name,
                'code' => $siding->code,
            ],
            'rakeData' => $rakeData,
            'subscribable_sidings' => [(int) $siding->id],
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Return every Loadrite event for a rake ordered chronologically.
     * Powers the time-scrubber replay on /control-panel-2/{siding}.
     */
    public function rakeReplay(Request $request, Rake $rake): JsonResponse
    {
        $rake->loadMissing('siding:id');

        if ($rake->siding_id === null || ! $this->userCanSeeSiding($request->user(), (int) $rake->siding_id)) {
            throw new AccessDeniedHttpException('You do not have access to this rake.');
        }

        $events = DB::table('loadrite_events')
            ->where('rake_id', $rake->id)
            ->orderBy('event_time')
            ->orderBy('id')
            ->get([
                'event_id',
                'event_type',
                'weight_mt',
                'event_time',
                'wagon_id',
                'wagon_sequence',
                'operator',
                'scale_id',
            ]);

        return response()->json([
            'rake_id' => (int) $rake->id,
            'wagon_count' => (int) ($rake->wagon_count ?? 0),
            'first_event_at' => $events->first()->event_time ?? null,
            'last_event_at' => $events->last()->event_time ?? null,
            'events' => $events,
        ]);
    }

    /**
     * Return the chronological Loadrite event timeline for a single wagon.
     * Powers the wagon-click drawer on /control-panel-2/{siding}.
     */
    public function wagonTimeline(Request $request, Wagon $wagon): JsonResponse
    {
        $wagon->loadMissing('rake:id,siding_id');
        $sidingId = $wagon->rake?->siding_id;

        if ($sidingId === null || ! $this->userCanSeeSiding($request->user(), (int) $sidingId)) {
            throw new AccessDeniedHttpException('You do not have access to this wagon.');
        }

        $events = DB::table('loadrite_events')
            ->where('wagon_id', $wagon->id)
            ->orderByDesc('event_time')
            ->orderByDesc('id')
            ->limit(50)
            ->get([
                'event_id',
                'event_type',
                'weight_mt',
                'event_time',
                'operator',
                'scale_id',
                'product',
                'wagon_sequence',
            ]);

        return response()->json([
            'wagon' => [
                'id' => (int) $wagon->id,
                'wagon_number' => $wagon->wagon_number,
                'wagon_sequence' => $wagon->wagon_sequence,
                'wagon_type' => $wagon->wagon_type,
                'pcc_weight_mt' => $wagon->pcc_weight_mt,
            ],
            'events' => $events,
        ]);
    }

    private function activeRakeForSiding(Siding $siding): ?Rake
    {
        $withActivity = Rake::query()
            ->where('siding_id', $siding->id)
            ->where(function ($q): void {
                $q->whereNull('state')->orWhereNotIn('state', ['cancelled', 'dispatched', 'completed']);
            })
            ->whereExists(function ($q): void {
                $q->select(DB::raw(1))
                    ->from('wagon_loading')
                    ->whereColumn('wagon_loading.rake_id', 'rakes.id')
                    ->whereNotNull('wagon_loading.loaded_quantity_mt')
                    ->whereRaw('wagon_loading.loaded_quantity_mt::numeric > 0');
            })
            ->orderByRaw(
                '(SELECT MAX(updated_at) FROM wagon_loading wl WHERE wl.rake_id = rakes.id) DESC',
            )
            ->first();

        if ($withActivity) {
            return $withActivity;
        }

        return Rake::query()
            ->where('siding_id', $siding->id)
            ->where(function ($q): void {
                $q->whereNull('state')->orWhereNotIn('state', ['cancelled', 'dispatched', 'completed']);
            })
            ->orderByDesc('placement_time')
            ->orderByDesc('loading_date')
            ->orderByDesc('id')
            ->first();
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

<?php

declare(strict_types=1);

namespace App\Services\LiveMonitor;

use App\Enums\LiveWagonStatus;
use App\Models\Alert;
use App\Models\Loader;
use App\Models\Rake;
use App\Models\RakeWagonLoading;
use App\Models\Siding;
use App\Models\User;
use App\Models\Wagon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Builds the JSON payload consumed by the Control Room (Live Monitor) Inertia pages.
 *
 * Every value rendered on the dashboard is sourced from this builder. When a field
 * is null in the database (e.g. a rake without placement_time), the builder returns
 * null and the UI renders an em-dash. The builder never fabricates values.
 *
 * When Loadrite ingestion lands (v2), the wagon_loading rows will gain populated
 * loadrite_weight_mt / loadrite_last_synced_at columns. This builder already reads
 * loaded_quantity_mt as the unified weight source — Loadrite's contribution becomes
 * transparent.
 */
final readonly class LiveMonitorDataBuilder
{
    public function __construct(
        private WagonStatusResolver $wagonStatus,
        private LoaderActivityResolver $loaderActivity,
    ) {}

    /**
     * Multi-siding overview payload.
     *
     * @return array{
     *     sidings: list<array<string, mixed>>,
     *     totals: array{sidings: int, rakes_active: int, alerts_active: int},
     *     last_event_at: string|null
     * }
     */
    public function forOverview(User $user): array
    {
        $sidingIds = $this->visibleSidingIds($user);

        if ($sidingIds === []) {
            return [
                'sidings' => [],
                'totals' => ['sidings' => 0, 'rakes_active' => 0, 'alerts_active' => 0],
                'last_event_at' => null,
            ];
        }

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get();

        $payloads = [];
        $rakesActive = 0;
        $alertsActive = 0;
        $latestEvent = null;

        foreach ($sidings as $siding) {
            $rake = $this->activeRakeForSiding($siding);
            if ($rake !== null) {
                $rakesActive++;
            }

            $sidingPayload = $this->buildSidingOverview($siding, $rake);
            $alertsActive += $sidingPayload['alerts_count'];

            if ($sidingPayload['last_event_at'] !== null
                && ($latestEvent === null || $sidingPayload['last_event_at'] > $latestEvent)) {
                $latestEvent = $sidingPayload['last_event_at'];
            }

            $payloads[] = $sidingPayload;
        }

        return [
            'sidings' => $payloads,
            'totals' => [
                'sidings' => $sidings->count(),
                'rakes_active' => $rakesActive,
                'alerts_active' => $alertsActive,
            ],
            'last_event_at' => $latestEvent,
        ];
    }

    /**
     * Single-rake deep payload.
     *
     * @return array<string, mixed>
     */
    public function forRake(Rake $rake): array
    {
        $rake->loadMissing(['siding']);

        $loadings = RakeWagonLoading::query()
            ->where('rake_id', $rake->id)
            ->with('wagon:id,wagon_number,wagon_sequence,wagon_type,pcc_weight_mt,is_unfit,tare_weight_mt')
            ->get();

        $wagons = $rake->wagons()
            ->orderBy('wagon_sequence')
            ->get(['id', 'wagon_number', 'wagon_sequence', 'wagon_type', 'pcc_weight_mt', 'is_unfit', 'tare_weight_mt']);

        $loadingByWagon = $loadings->keyBy('wagon_id');

        $loaders = $rake->siding
            ? Loader::query()->where('siding_id', $rake->siding_id)->where('is_active', true)->get()
            : collect();

        $wagonCards = $this->buildWagonCards($wagons, $loadingByWagon);
        $statusCounts = $this->countByStatus($wagonCards);

        $totalLoadedMt = (float) $loadings->sum('loaded_quantity_mt');
        $totalOverloadMt = $this->sumOverload($wagons, $loadingByWagon);
        $totalUnderloadMt = $this->sumUnderload($wagons, $loadingByWagon);
        $avgNetMt = $loadings->avg(fn (RakeWagonLoading $r) => (float) ($r->loaded_quantity_mt ?? 0));

        $loaderActivity = $this->loaderActivity->resolveForRake($loaders, $loadings);

        $timeStatus = $this->buildTimeStatus($rake, $loadings);

        $loadingProgress = $this->buildLoadingProgress($wagonCards, $rake);

        $sourceCounts = $this->countBySource($wagonCards);

        $alerts = $this->recentAlerts(siding_id: $rake->siding_id, rake_id: $rake->id, limit: 8);

        // Prefer the last LOADRITE event time so the stale indicator and idle
        // detection use the true last loading activity, not a reattribution
        // timestamp on wagon_loading.updated_at.
        $lastLoadriteAt = DB::table('loadrite_events')
            ->where('rake_id', $rake->id)
            ->max('event_time');
        $lastEventAt = ($lastLoadriteAt ? CarbonImmutable::parse($lastLoadriteAt) : ($loadings->max('updated_at') ?? null))?->toIso8601String();

        return [
            'rake' => [
                'id' => (int) $rake->id,
                'rake_number' => $rake->rake_number,
                'rake_type' => $rake->rake_type,
                'wagon_count' => (int) ($rake->wagon_count ?? $wagons->count()),
                'state' => $rake->state,
                'loading_date' => $rake->loading_date?->toDateString(),
                'siding' => $rake->siding ? [
                    'id' => (int) $rake->siding->id,
                    'name' => $rake->siding->name,
                    'code' => $rake->siding->code,
                ] : null,
            ],
            'kpis' => [
                'total_loaded_mt' => round($totalLoadedMt, 2),
                'total_overload_mt' => round($totalOverloadMt, 2),
                'total_underload_mt' => round($totalUnderloadMt, 2),
                'avg_net_mt' => $avgNetMt !== null ? round((float) $avgNetMt, 2) : null,
                'placement_time' => $rake->placement_time?->toIso8601String(),
                'loading_start_time' => $rake->loading_start_time?->toIso8601String(),
                'loading_end_time' => $rake->loading_end_time?->toIso8601String(),
                'loading_elapsed_minutes' => $timeStatus['elapsed_minutes'],
            ],
            'status_counts' => $statusCounts,
            'source_counts' => $sourceCounts,
            'wagons' => $wagonCards,
            'loaders' => $loaderActivity,
            'time_status' => $timeStatus,
            'loading_progress' => $loadingProgress,
            'alerts' => $alerts,
            'last_event_at' => $lastEventAt,
        ];
    }

    /**
     * @return list<int>
     */
    private function visibleSidingIds(User $user): array
    {
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return Siding::query()->where('is_active', true)->pluck('id')->all();
        }

        $ids = $user->sidings()->pluck('sidings.id')->all();
        if ($ids === [] && $user->siding_id !== null) {
            return [(int) $user->siding_id];
        }

        return array_map('intval', $ids);
    }

    private function activeRakeForSiding(Siding $siding): ?Rake
    {
        // Two-pass selection:
        //   1) Rake with the most recent wagon_loading activity at this siding
        //      (an actually-loaded rake operators care about).
        //   2) Fallback: a newly placed rake with no loadings yet, so operators
        //      still see it before the first weight arrives.
        $withActivity = Rake::query()
            ->where('siding_id', $siding->id)
            ->where(function ($q) {
                $q->whereNull('state')->orWhereNotIn('state', ['cancelled', 'dispatched', 'completed']);
            })
            ->whereExists(function ($q) {
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
            ->where(function ($q) {
                $q->whereNull('state')->orWhereNotIn('state', ['cancelled', 'dispatched', 'completed']);
            })
            ->orderByDesc('placement_time')
            ->orderByDesc('loading_date')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSidingOverview(Siding $siding, ?Rake $rake): array
    {
        if ($rake === null) {
            return [
                'siding_id' => (int) $siding->id,
                'siding_name' => $siding->name,
                'siding_code' => $siding->code,
                'has_active_rake' => false,
                'rake' => null,
                'kpis' => null,
                'wagons' => [],
                'status_counts' => [],
                'loaders' => [],
                'time_status' => null,
                'loading_progress' => null,
                'alerts_count' => 0,
                'last_event_at' => null,
            ];
        }

        $loadings = RakeWagonLoading::query()
            ->where('rake_id', $rake->id)
            ->with('wagon:id,wagon_number,wagon_sequence,wagon_type,pcc_weight_mt,is_unfit,tare_weight_mt')
            ->get();

        $wagons = $rake->wagons()
            ->orderBy('wagon_sequence')
            ->get(['id', 'wagon_number', 'wagon_sequence', 'wagon_type', 'pcc_weight_mt', 'is_unfit']);

        $loadingByWagon = $loadings->keyBy('wagon_id');
        $wagonCards = $this->buildWagonCards($wagons, $loadingByWagon);
        $statusCounts = $this->countByStatus($wagonCards);

        $loaders = Loader::query()->where('siding_id', $siding->id)->where('is_active', true)->get();

        return [
            'siding_id' => (int) $siding->id,
            'siding_name' => $siding->name,
            'siding_code' => $siding->code,
            'has_active_rake' => true,
            'rake' => [
                'id' => (int) $rake->id,
                'rake_number' => $rake->rake_number,
                'wagon_count' => (int) ($rake->wagon_count ?? $wagons->count()),
                'state' => $rake->state,
            ],
            'kpis' => [
                'total_loaded_mt' => round((float) $loadings->sum('loaded_quantity_mt'), 2),
                'total_overload_mt' => round($this->sumOverload($wagons, $loadingByWagon), 2),
                'total_underload_mt' => round($this->sumUnderload($wagons, $loadingByWagon), 2),
            ],
            'wagons' => $wagonCards,
            'status_counts' => $statusCounts,
            'source_counts' => $this->countBySource($wagonCards),
            'loaders' => $this->loaderActivity->resolveForRake($loaders, $loadings),
            'time_status' => $this->buildTimeStatus($rake, $loadings),
            'loading_progress' => $this->buildLoadingProgress($wagonCards, $rake),
            'alerts_count' => Alert::query()
                ->where('siding_id', $siding->id)
                ->where('status', 'active')
                ->count(),
            'last_event_at' => $loadings->max('updated_at')?->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, Wagon>  $wagons
     * @param  Collection<int, RakeWagonLoading>  $loadingByWagon
     * @return list<array<string, mixed>>
     */
    private function buildWagonCards(Collection $wagons, Collection $loadingByWagon): array
    {
        $cards = [];
        foreach ($wagons as $wagon) {
            /** @var RakeWagonLoading|null $loading */
            $loading = $loadingByWagon->get($wagon->id);
            $status = $this->wagonStatus->resolve($wagon, $loading);

            $loadedMt = $loading?->loaded_quantity_mt !== null
                ? (float) $loading->loaded_quantity_mt
                : null;
            $pcc = $wagon->pcc_weight_mt !== null ? (float) $wagon->pcc_weight_mt : null;

            $overloadMt = ($loadedMt !== null && $pcc !== null && $loadedMt > $pcc)
                ? round($loadedMt - $pcc, 2)
                : null;

            $cards[] = [
                'wagon_id' => (int) $wagon->id,
                'wagon_number' => $wagon->wagon_number,
                'wagon_sequence' => (int) ($wagon->wagon_sequence ?? 0),
                'wagon_type' => $wagon->wagon_type,
                'cc_mt' => $pcc,
                'loaded_mt' => $loadedMt,
                'overload_mt' => $overloadMt,
                'status' => $status->value,
                'status_label' => $status->label(),
                'status_color' => $status->color(),
                'loader_id' => $loading?->loader_id !== null ? (int) $loading->loader_id : null,
                'last_updated_at' => $loading?->updated_at?->toIso8601String(),
                'weight_source' => $loading?->weight_source,
                'loadrite_override' => (bool) ($loading?->loadrite_override ?? false),
            ];
        }

        return $cards;
    }

    /**
     * @param  list<array<string, mixed>>  $wagonCards
     * @return array<string, int>
     */
    private function countByStatus(array $wagonCards): array
    {
        $counts = array_fill_keys(
            array_map(fn (LiveWagonStatus $s): string => $s->value, LiveWagonStatus::cases()),
            0
        );

        foreach ($wagonCards as $card) {
            $counts[$card['status']]++;
        }

        return $counts;
    }

    /**
     * @param  list<array<string, mixed>>  $wagonCards
     * @return array<string, int>
     */
    private function countBySource(array $wagonCards): array
    {
        $counts = ['loadrite' => 0, 'manual' => 0, 'weighbridge' => 0, 'none' => 0, 'override' => 0];
        foreach ($wagonCards as $card) {
            if ($card['loaded_mt'] === null || $card['loaded_mt'] === 0.0) {
                $counts['none']++;

                continue;
            }
            $src = $card['weight_source'] ?? 'manual';
            if (! isset($counts[$src])) {
                $counts[$src] = 0;
            }
            $counts[$src]++;
            if (! empty($card['loadrite_override'])) {
                $counts['override']++;
            }
        }

        return $counts;
    }

    private function sumOverload(Collection $wagons, Collection $loadingByWagon): float
    {
        $sum = 0.0;
        foreach ($wagons as $wagon) {
            $loading = $loadingByWagon->get($wagon->id);
            $loaded = (float) ($loading?->loaded_quantity_mt ?? 0);
            $pcc = (float) ($wagon->pcc_weight_mt ?? 0);
            if ($pcc > 0 && $loaded > $pcc) {
                $sum += ($loaded - $pcc);
            }
        }

        return $sum;
    }

    private function sumUnderload(Collection $wagons, Collection $loadingByWagon): float
    {
        $sum = 0.0;
        foreach ($wagons as $wagon) {
            if ($wagon->is_unfit) {
                continue;
            }
            $loading = $loadingByWagon->get($wagon->id);
            $loaded = (float) ($loading?->loaded_quantity_mt ?? 0);
            $pcc = (float) ($wagon->pcc_weight_mt ?? 0);
            if ($pcc > 0 && $loaded > 0 && $loaded < $pcc) {
                $sum += ($pcc - $loaded);
            }
        }

        return $sum;
    }

    /**
     * @return array{
     *     anchor: string,
     *     anchor_at: string|null,
     *     elapsed_minutes: int|null,
     *     allowed_minutes: int,
     *     remaining_minutes: int|null,
     *     overrun_minutes: int|null
     * }
     */
    private function buildTimeStatus(Rake $rake, Collection $loadings): array
    {
        $allowedMinutes = (int) ($rake->loading_free_minutes ?? 180);

        // "Loading time" means the actual span of loading activity at the wagons,
        // NOT the time since placement. Placement_time is often set hours-to-weeks
        // before actual loading begins, so anchoring elapsed on it produced absurd
        // figures like "47,302 min" / "566 hours".
        //
        // Anchor = first observed activity for this rake (loadrite event OR
        // wagon_loading row update). End = last observed activity, capped at
        // loading_end_time when an explicit end is recorded.
        $firstLoadriteEventAt = DB::table('loadrite_events')
            ->where('rake_id', $rake->id)
            ->min('event_time');
        $lastLoadriteEventAt = DB::table('loadrite_events')
            ->where('rake_id', $rake->id)
            ->max('event_time');

        $firstWagonLoadingAt = $loadings
            ->filter(fn ($l) => $l->loaded_quantity_mt !== null)
            ->min('updated_at');
        $lastWagonLoadingAt = $loadings
            ->filter(fn ($l) => $l->loaded_quantity_mt !== null)
            ->max('updated_at');

        $candidates = array_filter([
            $firstLoadriteEventAt ? CarbonImmutable::parse($firstLoadriteEventAt) : null,
            $firstWagonLoadingAt ? CarbonImmutable::parse($firstWagonLoadingAt) : null,
        ]);

        // Fall back to placement_time / loading_start_time only when we have no
        // activity timestamps at all (otherwise activity always wins — that's
        // the actual loading window).
        $anchorAt = ! empty($candidates)
            ? collect($candidates)->min()
            : ($rake->loading_start_time ?? $rake->placement_time ?? null);

        $anchorLabel = match (true) {
            ! empty($candidates) && $firstLoadriteEventAt !== null && (! $firstWagonLoadingAt || $firstLoadriteEventAt <= $firstWagonLoadingAt) => 'first_loadrite_event',
            ! empty($candidates) => 'first_wagon_loading',
            $rake->loading_start_time !== null => 'loading_start',
            $rake->placement_time !== null => 'placement',
            default => 'unknown',
        };

        if ($anchorAt === null) {
            return [
                'anchor' => $anchorLabel,
                'anchor_at' => null,
                'elapsed_minutes' => null,
                'allowed_minutes' => $allowedMinutes,
                'remaining_minutes' => null,
                'overrun_minutes' => null,
            ];
        }

        $endCandidates = array_filter([
            $rake->loading_end_time ? CarbonImmutable::parse($rake->loading_end_time) : null,
            $lastLoadriteEventAt ? CarbonImmutable::parse($lastLoadriteEventAt) : null,
            $lastWagonLoadingAt ? CarbonImmutable::parse($lastWagonLoadingAt) : null,
        ]);

        $end = ! empty($endCandidates)
            ? collect($endCandidates)->max()
            : CarbonImmutable::now();

        $elapsed = (int) CarbonImmutable::parse($anchorAt)->diffInMinutes(CarbonImmutable::parse($end), true);

        $remaining = $rake->loading_end_time !== null
            ? null
            : max(0, $allowedMinutes - $elapsed);

        $overrun = ($rake->loading_end_time === null && $elapsed > $allowedMinutes)
            ? ($elapsed - $allowedMinutes)
            : null;

        return [
            'anchor' => $anchorLabel,
            'anchor_at' => CarbonImmutable::parse($anchorAt)->toIso8601String(),
            'elapsed_minutes' => $elapsed,
            'allowed_minutes' => $allowedMinutes,
            'remaining_minutes' => $remaining,
            'overrun_minutes' => $overrun,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $wagonCards
     * @return array{loaded: int, total: int, percent: float}
     */
    private function buildLoadingProgress(array $wagonCards, Rake $rake): array
    {
        $total = (int) ($rake->wagon_count ?? count($wagonCards));
        if ($total === 0) {
            $total = count($wagonCards);
        }

        $loaded = 0;
        foreach ($wagonCards as $card) {
            if (in_array($card['status'], [LiveWagonStatus::Loaded->value, LiveWagonStatus::Overload->value], true)) {
                $loaded++;
            }
        }

        $percent = $total > 0 ? round(($loaded / $total) * 100, 1) : 0.0;

        return ['loaded' => $loaded, 'total' => $total, 'percent' => $percent];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentAlerts(int $siding_id, ?int $rake_id, int $limit): array
    {
        $query = Alert::query()
            ->where('siding_id', $siding_id)
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($rake_id !== null) {
            $query->where(function ($q) use ($rake_id): void {
                $q->where('rake_id', $rake_id)->orWhereNull('rake_id');
            });
        }

        return $query->get([
            'id', 'type', 'title', 'body', 'severity', 'status', 'rake_id', 'created_at',
        ])->map(fn (Alert $a): array => [
            'id' => (int) $a->id,
            'type' => $a->type,
            'title' => $a->title,
            'body' => $a->body,
            'severity' => $a->severity,
            'status' => $a->status,
            'rake_id' => $a->rake_id !== null ? (int) $a->rake_id : null,
            'created_at' => $a->created_at?->toIso8601String(),
        ])->all();
    }
}

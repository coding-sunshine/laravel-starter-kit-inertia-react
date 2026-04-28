<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Models\Loader;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Wagon load vs effective CC (wagon_loading + wagons) scoped by rake loading_date.
 * Used by the executive dashboard (mobile) and web lazy loader/operator performance APIs.
 */
final class LoaderOverloadMetricsService
{
    /**
     * @param  array<int>  $sidingIds
     * @param  array{power_plant?: string|null, rake_number?: string|null, loader_id?: int|null, loader_operator_name?: string|null, shift?: string|null, underload_threshold_percent?: float}  $filterContext
     * @return array{loaders: array<int, array{id: int, name: string, siding: string, operators: array<int, string>}>, monthly: array<int, array<string, mixed>>}
     */
    public function buildLoaderOverloadTrends(
        array $sidingIds,
        CarbonInterface $from,
        CarbonInterface $to,
        array $filterContext = [],
    ): array {
        if ($sidingIds === []) {
            return ['loaders' => [], 'monthly' => []];
        }

        $fromDate = Carbon::parse($from)->toDateString();
        $toDate = Carbon::parse($to)->toDateString();

        $underloadThresholdPercent = isset($filterContext['underload_threshold_percent'])
            ? max(0.0, min(100.0, (float) $filterContext['underload_threshold_percent']))
            : 1.0;

        $loaders = Loader::query()
            ->whereIn('siding_id', $sidingIds)
            ->with('siding:id,name')
            ->orderBy('loader_name')
            ->get(['id', 'loader_name', 'siding_id']);

        if ($loaders->isEmpty()) {
            return ['loaders' => [], 'monthly' => []];
        }

        $loaderIds = $loaders->pluck('id')->all();
        $loaderMap = $loaders->mapWithKeys(fn (Loader $l): array => [$l->id => $l->loader_name])->all();
        $loaderOperatorsByLoader = $this->collectOperatorsByLoaderId($loaderIds);

        $wlQuery = $this->baseWagonLoadingQuery($sidingIds, $fromDate, $toDate, $loaderIds, $filterContext);
        $ym = $this->yearMonthSelectAndGroupByLoaderTrends();
        $rows = $wlQuery
            ->selectRaw(
                "{$ym['select']}, wl.loader_id, count(*) as wagons_loaded, {$this->overloadUnderloadSums($underloadThresholdPercent)}",
                [$underloadThresholdPercent],
            )
            ->groupByRaw($ym['groupBy'])
            ->get();

        if ($rows->isEmpty()) {
            return [
                'loaders' => $loaders->map(fn (Loader $l): array => [
                    'id' => $l->id,
                    'name' => $l->loader_name,
                    'siding' => $l->siding?->name ?? '—',
                    'operators' => $loaderOperatorsByLoader[$l->id] ?? [],
                ])->values()->all(),
                'monthly' => [],
            ];
        }

        $minYm = null;
        $maxYm = null;
        foreach ($rows as $r) {
            $ym = ((int) $r->y) * 100 + (int) $r->m;
            $minYm = $minYm === null ? $ym : min($minYm, $ym);
            $maxYm = $maxYm === null ? $ym : max($maxYm, $ym);
        }

        $months = [];
        $cursor = Carbon::create(intdiv((int) $minYm, 100), (int) $minYm % 100, 1)->startOfMonth();
        $endMonth = Carbon::create(intdiv((int) $maxYm, 100), (int) $maxYm % 100, 1)->startOfMonth();
        while ($cursor->lte($endMonth)) {
            $key = $cursor->format('Y-m');
            $months[$key] = ['month' => $cursor->format('M Y')];
            foreach ($loaderMap as $id => $name) {
                $months[$key]["loader_{$id}_overload"] = 0;
                $months[$key]["loader_{$id}_underload"] = 0;
                $months[$key]["loader_{$id}_total"] = 0;
            }
            $cursor->addMonth();
        }

        foreach ($rows as $r) {
            $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            if (isset($months[$key])) {
                $months[$key]["loader_{$r->loader_id}_overload"] = (int) $r->overloaded_wagons;
                $months[$key]["loader_{$r->loader_id}_underload"] = (int) $r->underloaded_wagons;
                $months[$key]["loader_{$r->loader_id}_total"] = (int) $r->wagons_loaded;
            }
        }

        return [
            'loaders' => $loaders->map(fn (Loader $l): array => [
                'id' => $l->id,
                'name' => $l->loader_name,
                'siding' => $l->siding?->name ?? '—',
                'operators' => $loaderOperatorsByLoader[$l->id] ?? [],
            ])->values()->all(),
            'monthly' => array_values($months),
        ];
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{loader_id?: int|null, loader_operator_name?: string|null, underload_threshold_percent?: float}  $filterContext
     * @return LengthAwarePaginator<int, array{id: int, name: string, siding: string}>
     */
    public function paginateLoadersWithActivity(
        array $sidingIds,
        CarbonInterface $from,
        CarbonInterface $to,
        array $filterContext,
        int $perPage,
        int $page,
    ): LengthAwarePaginator {
        if ($sidingIds === []) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();

        $sub = DB::table('wagon_loading as wl')
            ->join('rakes as r', 'r.id', '=', 'wl.rake_id')
            ->whereIn('r.siding_id', $sidingIds)
            ->whereNotNull('r.loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('r.loading_date', true), [$fromDate, $toDate]);

        if (! empty($filterContext['loader_id'])) {
            $sub->where('wl.loader_id', (int) $filterContext['loader_id']);
        }
        if (! empty($filterContext['loader_operator_name'])) {
            $this->applyLoaderOperatorNameFilterToQuery($sub, (string) $filterContext['loader_operator_name'], 'wl');
        }

        $loaderIds = $sub->distinct()->pluck('wl.loader_id')->filter()->values()->all();
        if ($loaderIds === []) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $paginator = Loader::query()
            ->whereIn('id', $loaderIds)
            ->whereIn('siding_id', $sidingIds)
            ->with('siding:id,name')
            ->orderBy('loader_name')
            ->paginate($perPage, ['id', 'loader_name', 'siding_id'], 'page', $page);
        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Loader $l): array => [
                'id' => $l->id,
                'name' => $l->loader_name,
                'siding' => $l->siding?->name ?? '—',
            ]),
        );

        return $paginator;
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{underload_threshold_percent?: float, loader_id?: int|null, loader_operator_name?: string|null}  $filterContext
     * @return array{loader: array{id: int, name: string, siding: string}, operators: list<string>, monthly: list<array{month: string, overload: int, underload: int, total: int}>, summary: array{total_wagons: int, overloaded_wagons: int, underloaded_wagons: int, overload_rate: float, underload_rate: float, overload_trend: int, underload_trend: int}}
     */
    public function loaderDetail(
        Loader $loader,
        array $sidingIds,
        CarbonInterface $from,
        CarbonInterface $to,
        array $filterContext,
    ): ?array {
        if ($sidingIds === [] || ! in_array($loader->siding_id, $sidingIds, true)) {
            return null;
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $underloadThresholdPercent = isset($filterContext['underload_threshold_percent'])
            ? max(0.0, min(100.0, (float) $filterContext['underload_threshold_percent']))
            : 1.0;

        $context = array_merge($filterContext, ['loader_id' => $loader->id]);
        $wlQuery = $this->baseWagonLoadingQuery(
            $sidingIds,
            $fromDate,
            $toDate,
            [(int) $loader->id],
            $context,
        );

        $ym = $this->yearMonthSelectAndGroupByMonthOnly();
        $rows = $wlQuery
            ->selectRaw(
                "{$ym['select']}, count(*) as wagons_loaded, {$this->overloadUnderloadSums($underloadThresholdPercent)}",
                [$underloadThresholdPercent],
            )
            ->groupByRaw($ym['groupBy'])->get();

        $loader->load('siding:id,name');
        $operatorNames = $this->operatorNamesForLoaderInRange((int) $loader->id, $sidingIds, $fromDate, $toDate, $filterContext);
        $monthly = $this->buildSimpleMonthlyFromRows($rows);
        $summary = $this->summaryFromSimpleMonthly($monthly);

        return [
            'loader' => [
                'id' => (int) $loader->id,
                'name' => (string) $loader->loader_name,
                'siding' => $loader->siding?->name ?? '—',
            ],
            'operators' => $operatorNames,
            'monthly' => $monthly,
            'summary' => $summary,
        ];
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{loader_id?: int|null, loader_operator_name?: string|null}  $filterContext
     * @return LengthAwarePaginator<int, array{siding_id: int, siding: string, name: string}>
     */
    public function paginateOperatorsWithActivity(
        array $sidingIds,
        CarbonInterface $from,
        CarbonInterface $to,
        array $filterContext,
        int $perPage,
        int $page,
    ): LengthAwarePaginator {
        if ($sidingIds === []) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();

        $pairs = DB::table('wagon_loading as wl')
            ->join('rakes as r', 'r.id', '=', 'wl.rake_id')
            ->whereIn('r.siding_id', $sidingIds)
            ->whereNotNull('r.loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('r.loading_date', true), [$fromDate, $toDate])
            ->whereNotNull('wl.loader_operator_name')
            ->where('wl.loader_operator_name', '!=', '')
            ->selectRaw('r.siding_id, TRIM(wl.loader_operator_name) as op_name')
            ->distinct()
            ->get();

        if ($pairs->isEmpty()) {
            return new LengthAwarePaginator([], 0, $perPage, $page);
        }

        $sidingNames = \App\Models\Siding::query()
            ->whereIn('id', $pairs->pluck('siding_id')->unique())
            ->pluck('name', 'id');

        $uniques = $pairs->map(fn ($p): array => [
            'siding_id' => (int) $p->siding_id,
            'name' => (string) $p->op_name,
        ])->unique(fn (array $a): string => $a['siding_id'].'|'.$a['name'])->values();

        $total = $uniques->count();
        $slice = $uniques->sortBy(['name', 'siding_id'])->slice(($page - 1) * $perPage, $perPage)->values();

        $data = $slice->map(function (array $row) use ($sidingNames): array {
            $sid = $row['siding_id'];

            return [
                'siding_id' => $sid,
                'siding' => (string) ($sidingNames[$sid] ?? '—'),
                'name' => $row['name'],
            ];
        })->all();

        return new LengthAwarePaginator(
            $data,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );
    }

    /**
     * @param  array<int>  $sidingIds
     * @param  array{underload_threshold_percent?: float, loader_id?: int|null, loader_operator_name?: string|null}  $filterContext
     * @return array{operator: array{name: string, siding_id: int, siding: string}, loaders: list<array{id: int, name: string}>, monthly: list<array{month: string, overload: int, underload: int, total: int}>, summary: array{total_wagons: int, overloaded_wagons: int, underloaded_wagons: int, overload_rate: float, underload_rate: float, overload_trend: int, underload_trend: int}}|null
     */
    public function operatorDetail(
        int $sidingId,
        string $operatorName,
        array $sidingIds,
        CarbonInterface $from,
        CarbonInterface $to,
        array $filterContext,
    ): ?array {
        if ($sidingIds === [] || ! in_array($sidingId, $sidingIds, true)) {
            return null;
        }

        $normalized = mb_trim($operatorName);
        if ($normalized === '') {
            return null;
        }

        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();
        $underloadThresholdPercent = isset($filterContext['underload_threshold_percent'])
            ? max(0.0, min(100.0, (float) $filterContext['underload_threshold_percent']))
            : 1.0;

        $siding = \App\Models\Siding::query()->find($sidingId);
        if ($siding === null) {
            return null;
        }

        $ccEff = 'COALESCE(wl.cc_capacity_mt, w.pcc_weight_mt)';
        $overloadCase = "sum(CASE WHEN wl.loaded_quantity_mt IS NOT NULL AND {$ccEff} IS NOT NULL AND {$ccEff} > 0 AND wl.loaded_quantity_mt > {$ccEff} THEN 1 ELSE 0 END) as overloaded_wagons";
        $underloadCase = "sum(CASE WHEN wl.loaded_quantity_mt IS NOT NULL AND {$ccEff} IS NOT NULL AND {$ccEff} > 0 AND wl.loaded_quantity_mt < {$ccEff} AND (({$ccEff} - wl.loaded_quantity_mt) * 100.0 / {$ccEff}) >= ? THEN 1 ELSE 0 END) as underloaded_wagons";

        $ym = $this->yearMonthSelectAndGroupByMonthOnly();
        $q = DB::table('wagon_loading as wl')
            ->join('rakes as r', 'r.id', '=', 'wl.rake_id')
            ->join('wagons as w', 'w.id', '=', 'wl.wagon_id')
            ->where('r.siding_id', $sidingId)
            ->whereIn('r.siding_id', $sidingIds)
            ->where('wl.loader_operator_name', $normalized)
            ->whereNotNull('r.loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('r.loading_date', true), [$fromDate, $toDate]);

        $rows = (clone $q)
            ->selectRaw(
                "{$ym['select']}, count(*) as wagons_loaded, {$overloadCase}, {$underloadCase}",
                [$underloadThresholdPercent],
            )
            ->groupByRaw($ym['groupBy'])->get();

        $loaders = $q->select('wl.loader_id')
            ->distinct()
            ->pluck('loader_id')
            ->filter()
            ->values();

        $loaderModels = $loaders->isNotEmpty()
            ? Loader::query()
                ->whereIn('id', $loaders->all())
                ->orderBy('loader_name')
                ->get(['id', 'loader_name'])
                ->map(fn (Loader $l): array => ['id' => $l->id, 'name' => $l->loader_name])
                ->all()
            : [];

        $monthly = $this->buildSimpleMonthlyFromRows($rows);
        $summary = $this->summaryFromSimpleMonthly($monthly);

        return [
            'operator' => [
                'name' => $normalized,
                'siding_id' => $sidingId,
                'siding' => $siding->name,
            ],
            'loaders' => $loaderModels,
            'monthly' => $monthly,
            'summary' => $summary,
        ];
    }

    /**
     * @param  array<int>  $loaderIds
     * @return array<int, list<string>>
     */
    private function collectOperatorsByLoaderId(array $loaderIds): array
    {
        if ($loaderIds === []) {
            return [];
        }

        $loaderOperatorsByLoader = [];
        $operatorRows = DB::table('wagon_loading')
            ->whereIn('loader_id', $loaderIds)
            ->whereNotNull('loader_operator_name')
            ->where('loader_operator_name', '!=', '')
            ->select('loader_id', 'loader_operator_name')
            ->distinct()
            ->orderBy('loader_id')
            ->orderBy('loader_operator_name')
            ->get();

        foreach ($operatorRows as $operatorRow) {
            $loaderId = (int) $operatorRow->loader_id;
            if (! array_key_exists($loaderId, $loaderOperatorsByLoader)) {
                $loaderOperatorsByLoader[$loaderId] = [];
            }
            $loaderOperatorsByLoader[$loaderId][] = (string) $operatorRow->loader_operator_name;
        }

        return $loaderOperatorsByLoader;
    }

    /**
     * @param  array<int>  $loaderIds
     * @param  array{loader_id?: int|null, loader_operator_name?: string|null}  $filterContext
     */
    private function baseWagonLoadingQuery(
        array $sidingIds,
        string $fromDate,
        string $toDate,
        array $loaderIds,
        array $filterContext,
    ): Builder {
        $wlQuery = DB::table('wagon_loading as wl')
            ->join('rakes as r', 'r.id', '=', 'wl.rake_id')
            ->join('wagons as w', 'w.id', '=', 'wl.wagon_id')
            ->whereIn('r.siding_id', $sidingIds)
            ->whereIn('wl.loader_id', $loaderIds)
            ->whereNotNull('r.loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('r.loading_date', true), [$fromDate, $toDate]);

        if (! empty($filterContext['loader_id'])) {
            $wlQuery->where('wl.loader_id', (int) $filterContext['loader_id']);
        }

        if (! empty($filterContext['loader_operator_name'])) {
            $this->applyLoaderOperatorNameFilterToQuery($wlQuery, (string) $filterContext['loader_operator_name'], 'wl');
        }

        return $wlQuery;
    }

    private function overloadUnderloadSums(float $underloadThresholdPercent): string
    {
        $ccEff = 'COALESCE(wl.cc_capacity_mt, w.pcc_weight_mt)';

        return "sum(CASE WHEN wl.loaded_quantity_mt IS NOT NULL AND {$ccEff} IS NOT NULL AND {$ccEff} > 0 AND wl.loaded_quantity_mt > {$ccEff} THEN 1 ELSE 0 END) as overloaded_wagons, ".
            "sum(CASE WHEN wl.loaded_quantity_mt IS NOT NULL AND {$ccEff} IS NOT NULL AND {$ccEff} > 0 AND wl.loaded_quantity_mt < {$ccEff} AND (({$ccEff} - wl.loaded_quantity_mt) * 100.0 / {$ccEff}) >= ? THEN 1 ELSE 0 END) as underloaded_wagons";
    }

    /**
     * @return array{select: string, groupBy: string}
     */
    private function yearMonthSelectAndGroupByLoaderTrends(): array
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            return [
                'select' => 'EXTRACT(YEAR FROM r.loading_date)::int as y, EXTRACT(MONTH FROM r.loading_date)::int as m',
                'groupBy' => 'EXTRACT(YEAR FROM r.loading_date), EXTRACT(MONTH FROM r.loading_date), wl.loader_id',
            ];
        }
        if ($driver === 'sqlite') {
            return [
                'select' => "cast(strftime('%Y', r.loading_date) as int) as y, cast(strftime('%m', r.loading_date) as int) as m",
                'groupBy' => "strftime('%Y', r.loading_date), strftime('%m', r.loading_date), wl.loader_id",
            ];
        }

        return [
            'select' => 'YEAR(r.loading_date) as y, MONTH(r.loading_date) as m',
            'groupBy' => 'YEAR(r.loading_date), MONTH(r.loading_date), wl.loader_id',
        ];
    }

    /**
     * @return array{select: string, groupBy: string}
     */
    private function yearMonthSelectAndGroupByMonthOnly(): array
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            return [
                'select' => 'EXTRACT(YEAR FROM r.loading_date)::int as y, EXTRACT(MONTH FROM r.loading_date)::int as m',
                'groupBy' => 'EXTRACT(YEAR FROM r.loading_date), EXTRACT(MONTH FROM r.loading_date)',
            ];
        }
        if ($driver === 'sqlite') {
            return [
                'select' => "cast(strftime('%Y', r.loading_date) as int) as y, cast(strftime('%m', r.loading_date) as int) as m",
                'groupBy' => "strftime('%Y', r.loading_date), strftime('%m', r.loading_date)",
            ];
        }

        return [
            'select' => 'YEAR(r.loading_date) as y, MONTH(r.loading_date) as m',
            'groupBy' => 'YEAR(r.loading_date), MONTH(r.loading_date)',
        ];
    }

    private function buildSimpleMonthlyFromRows(Collection $rows): array
    {
        if ($rows->isEmpty()) {
            return [];
        }

        $minYm = null;
        $maxYm = null;
        foreach ($rows as $r) {
            $ym = ((int) $r->y) * 100 + (int) $r->m;
            $minYm = $minYm === null ? $ym : min($minYm, $ym);
            $maxYm = $maxYm === null ? $ym : max($maxYm, $ym);
        }

        $out = [];
        $cursor = Carbon::create(intdiv((int) $minYm, 100), (int) $minYm % 100, 1)->startOfMonth();
        $endMonth = Carbon::create(intdiv((int) $maxYm, 100), (int) $maxYm % 100, 1)->startOfMonth();
        $byKey = $rows->keyBy(fn ($r): string => sprintf('%04d-%02d', (int) $r->y, (int) $r->m));
        while ($cursor->lte($endMonth)) {
            $key = sprintf('%04d-%02d', (int) $cursor->year, (int) $cursor->month);
            $r = $byKey->get($key);
            $out[] = [
                'month' => $cursor->format('M Y'),
                'overload' => $r ? (int) $r->overloaded_wagons : 0,
                'underload' => $r ? (int) $r->underloaded_wagons : 0,
                'total' => $r ? (int) $r->wagons_loaded : 0,
            ];
            $cursor->addMonth();
        }

        return $out;
    }

    /**
     * @param  list<array{month: string, overload: int, underload: int, total: int}>  $monthly
     * @return array{total_wagons: int, overloaded_wagons: int, underloaded_wagons: int, overload_rate: float, underload_rate: float, overload_trend: int, underload_trend: int}
     */
    private function summaryFromSimpleMonthly(array $monthly): array
    {
        $totalWagons = 0;
        $totalOverload = 0;
        $totalUnderload = 0;
        foreach ($monthly as $m) {
            $totalWagons += $m['total'];
            $totalOverload += $m['overload'];
            $totalUnderload += $m['underload'];
        }
        $overloadRate = $totalWagons > 0 ? round(($totalOverload / $totalWagons) * 100, 1) : 0.0;
        $underloadRate = $totalWagons > 0 ? round(($totalUnderload / $totalWagons) * 100, 1) : 0.0;
        $lastTwo = array_slice($monthly, -2);
        $overloadTrend = count($lastTwo) === 2 ? $lastTwo[1]['overload'] - $lastTwo[0]['overload'] : 0;
        $underloadTrend = count($lastTwo) === 2 ? $lastTwo[1]['underload'] - $lastTwo[0]['underload'] : 0;

        return [
            'total_wagons' => $totalWagons,
            'overloaded_wagons' => $totalOverload,
            'underloaded_wagons' => $totalUnderload,
            'overload_rate' => $overloadRate,
            'underload_rate' => $underloadRate,
            'overload_trend' => $overloadTrend,
            'underload_trend' => $underloadTrend,
        ];
    }

    /**
     * @param  array{loader_id?: int|null, loader_operator_name?: string|null}  $filterContext
     * @return list<string>
     */
    private function operatorNamesForLoaderInRange(
        int $loaderId,
        array $sidingIds,
        string $fromDate,
        string $toDate,
        array $filterContext,
    ): array {
        $q = DB::table('wagon_loading as wl')
            ->join('rakes as r', 'r.id', '=', 'wl.rake_id')
            ->where('wl.loader_id', $loaderId)
            ->whereIn('r.siding_id', $sidingIds)
            ->whereNotNull('r.loading_date')
            ->whereRaw($this->dateOnlyBetweenSql('r.loading_date', true), [$fromDate, $toDate])
            ->whereNotNull('wl.loader_operator_name')
            ->where('wl.loader_operator_name', '!=', '');

        if (! empty($filterContext['loader_operator_name'])) {
            $this->applyLoaderOperatorNameFilterToQuery($q, (string) $filterContext['loader_operator_name'], 'wl');
        }

        return $q->select('wl.loader_operator_name')
            ->distinct()
            ->orderBy('wl.loader_operator_name')
            ->pluck('loader_operator_name')
            ->map(fn (mixed $n): string => (string) $n)
            ->values()
            ->all();
    }

    private function applyLoaderOperatorNameFilterToQuery(Builder $wlQuery, string $loaderOperatorName, string $tableAlias = 'wl'): void
    {
        $normalized = mb_trim($loaderOperatorName);
        if ($normalized === '') {
            return;
        }

        $wlQuery->where("{$tableAlias}.loader_operator_name", $normalized);
    }

    /**
     * @see \App\Http\Controllers\Dashboard\ExecutiveDashboardController::dateOnlyBetweenSql
     */
    private function dateOnlyBetweenSql(string $column, bool $columnIsPostgresDate = false): string
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            if ($columnIsPostgresDate) {
                return "({$column})::date BETWEEN ? AND ?";
            }

            $tz = config('app.timezone', 'UTC');
            $tzEscaped = str_replace("'", "''", $tz);

            return "(({$column} AT TIME ZONE 'UTC' AT TIME ZONE '{$tzEscaped}')::date) BETWEEN ? AND ?";
        }

        return "DATE({$column}) BETWEEN ? AND ?";
    }
}

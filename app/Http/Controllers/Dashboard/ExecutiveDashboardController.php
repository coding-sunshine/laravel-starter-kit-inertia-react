<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\CoalStock;
use App\Models\Indent;
use App\Models\Loader;
use App\Models\Penalty;
use App\Models\PowerPlantReceipt;
use App\Models\Rake;
use App\Models\RakeWeighment;
use App\Models\Siding;
use App\Models\StockLedger;
use App\Models\VehicleUnload;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class ExecutiveDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $allSidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $allSidings = Siding::query()
            ->whereIn('id', $allSidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Siding $s): array => ['id' => $s->id, 'name' => $s->name, 'code' => $s->code]);

        [$from, $to] = $this->resolveDateRange($request);

        $filteredSidingIds = $request->has('siding_ids')
            ? array_values(array_intersect($allSidingIds, array_map('intval', (array) $request->input('siding_ids', []))))
            : $allSidingIds;

        return Inertia::render('dashboard', [
            'sidings' => $allSidings,
            'filters' => [
                'period' => $request->input('period', 'month'),
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'siding_ids' => $filteredSidingIds,
            ],
            'sidingWiseMonthly' => $this->buildSidingWiseMonthly($filteredSidingIds, $from, $to),
            'sidingRadar' => $this->buildSidingRadar($filteredSidingIds, $from, $to),
            'sidingPerformance' => $this->buildSidingPerformance($filteredSidingIds, $from, $to),
            'sidingStocks' => $this->buildSidingStocks($filteredSidingIds),
            'dateWiseDispatch' => $this->buildDateWiseDispatch($filteredSidingIds, $from, $to),
            'rakePerformance' => $this->buildRakePerformance($filteredSidingIds, $from, $to),
            'loaderOverloadTrends' => $this->buildLoaderOverloadTrends($filteredSidingIds, $from, $to),
            'powerPlantDispatch' => $this->buildPowerPlantDispatch($filteredSidingIds, $from, $to),
        ]);
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function resolveDateRange(Request $request): array
    {
        $period = $request->input('period', 'month');
        $to = now()->endOfDay();

        $from = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            'custom' => $request->date('from') ? Carbon::parse($request->date('from'))->startOfDay() : now()->subMonth()->startOfDay(),
            default => now()->subMonth()->startOfDay(),
        };

        if ($period === 'custom' && $request->date('to')) {
            $to = Carbon::parse($request->date('to'))->endOfDay();
        }

        return [$from, $to];
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<string, mixed>
     */
    private function buildSummary(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [
                'rakesByState' => [],
                'totalRakes' => 0,
                'penaltiesThisMonth' => 0,
                'indentsPending' => 0,
                'indentsAcknowledged' => 0,
                'vehiclesReceivedToday' => 0,
            ];
        }

        $rakesByState = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->selectRaw('state, count(*) as count')
            ->groupBy('state')
            ->pluck('count', 'state')
            ->all();

        $totalRakes = (int) array_sum($rakesByState);

        $penaltiesThisMonth = Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('penalty_date', now()->month)
            ->whereYear('penalty_date', now()->year)
            ->sum('penalty_amount');

        $indentsPending = Indent::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereIn('state', ['pending', 'submitted'])
            ->count();

        $indentsAcknowledged = Indent::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotIn('state', ['pending', 'submitted'])
            ->count();

        $vehiclesReceivedToday = VehicleUnload::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereRaw('DATE(COALESCE(unload_end_time, created_at)) = ?', [today()->toDateString()])
            ->count();

        return [
            'rakesByState' => $rakesByState,
            'totalRakes' => $totalRakes,
            'penaltiesThisMonth' => (float) $penaltiesThisMonth,
            'indentsPending' => $indentsPending,
            'indentsAcknowledged' => $indentsAcknowledged,
            'vehiclesReceivedToday' => $vehiclesReceivedToday,
        ];
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<int, array<string, mixed>>
     */
    private function buildSidingStocks(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $latestLedgerIds = StockLedger::query()
            ->whereIn('siding_id', $sidingIds)
            ->selectRaw('max(id) as id')
            ->groupBy('siding_id')
            ->pluck('id')
            ->all();

        $byLedger = StockLedger::query()
            ->whereIn('id', $latestLedgerIds)
            ->get()
            ->keyBy('siding_id');

        $byCoalStock = CoalStock::query()
            ->whereIn('siding_id', $sidingIds)
            ->latest('as_of_date')
            ->get()
            ->unique('siding_id')
            ->keyBy('siding_id');

        $result = [];
        foreach ($sidingIds as $sid) {
            $ledger = $byLedger->get($sid);
            $coal = $byCoalStock->get($sid);
            $balance = $ledger
                ? (float) $ledger->closing_balance_mt
                : ($coal ? (float) $coal->closing_balance_mt : 0.0);
            $result[$sid] = [
                'siding_id' => $sid,
                'closing_balance_mt' => $balance,
            ];
        }

        return $result;
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<int, array<string, mixed>>
     */
    private function buildActiveRakes(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $rakes = Rake::query()
            ->with('siding:id,name,code')
            ->whereIn('siding_id', $sidingIds)
            ->where('state', 'loading')
            ->whereNotNull('placement_time')
            ->whereNotNull('loading_free_minutes')
            ->get();

        $list = [];
        foreach ($rakes as $rake) {
            $start = $rake->placement_time;
            $freeMinutes = (int) $rake->loading_free_minutes;
            $end = $start->copy()->addMinutes($freeMinutes);
            $remainingMinutes = max(0, (int) now()->diffInMinutes($end, false));
            $list[] = [
                'id' => $rake->id,
                'rake_number' => $rake->rake_number,
                'siding' => $rake->siding ? ['id' => $rake->siding->id, 'name' => $rake->siding->name, 'code' => $rake->siding->code] : null,
                'placement_time' => $rake->placement_time->toIso8601String(),
                'free_time_minutes' => $freeMinutes,
                'remaining_minutes' => $remainingMinutes,
            ];
        }

        return $list;
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<int, array{id: int, type: string, title: string, severity: string, rake_id: int|null, siding_id: int|null, created_at: string}>
     */
    private function buildAlerts(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        return Alert::query()
            ->active()
            ->forSidings($sidingIds)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'type', 'title', 'severity', 'rake_id', 'siding_id', 'created_at'])
            ->map(fn (Alert $a): array => [
                'id' => $a->id,
                'type' => $a->type,
                'title' => $a->title,
                'severity' => $a->severity,
                'rake_id' => $a->rake_id,
                'siding_id' => $a->siding_id,
                'created_at' => $a->created_at->toIso8601String(),
            ])
            ->values()
            ->all();
    }

    /**
     * Monthly penalty trend for last 12 months (backfilled with zeros).
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{month: string, total: float, count: int}>
     */
    private function buildPenaltyChartData(array $sidingIds): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $months[$key] = [
                'month' => $date->format('M Y'),
                'total' => 0.0,
                'count' => 0,
            ];
        }

        if ($sidingIds === []) {
            return array_values($months);
        }

        $driver = DB::getDriverName();
        $yearMonthSql = $driver === 'pgsql'
            ? 'EXTRACT(YEAR FROM penalty_date)::int as y, EXTRACT(MONTH FROM penalty_date)::int as m'
            : 'YEAR(penalty_date) as y, MONTH(penalty_date) as m';

        $rows = Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12))
            ->selectRaw("{$yearMonthSql}, sum(penalty_amount) as total, count(*) as count")
            ->groupBy('y', 'm')
            ->get();

        foreach ($rows as $r) {
            $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            if (isset($months[$key])) {
                $months[$key]['total'] = (float) $r->total;
                $months[$key]['count'] = (int) $r->count;
            }
        }

        return array_values($months);
    }

    /**
     * Penalties grouped by type (for pie chart).
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, value: float}>
     */
    private function buildPenaltyByType(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        return Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12))
            ->selectRaw('penalty_type, sum(penalty_amount) as total')
            ->groupBy('penalty_type')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r): array => [
                'name' => (string) $r->penalty_type,
                'value' => (float) $r->total,
            ])
            ->values()
            ->all();
    }

    /**
     * Penalties grouped by siding (for bar chart).
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, total: float}>
     */
    private function buildPenaltyBySiding(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        return Penalty::query()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->where('penalty_date', '>=', now()->subMonths(12))
            ->selectRaw('sidings.name, sum(penalty_amount) as total')
            ->groupBy('sidings.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r): array => [
                'name' => (string) $r->name,
                'total' => (float) $r->total,
            ])
            ->values()
            ->all();
    }

    /**
     * Cost avoidance: rakes that stayed within free time vs those that incurred penalties.
     *
     * @param  array<int>  $sidingIds
     * @return array{rakes_within_free_time: int, rakes_with_penalties: int, money_saved: float, money_lost: float}
     */
    private function buildCostAvoidance(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return ['rakes_within_free_time' => 0, 'rakes_with_penalties' => 0, 'money_saved' => 0, 'money_lost' => 0];
        }

        $thisMonth = now();
        $rakeIdsWithPenalties = Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('penalty_date', $thisMonth->month)
            ->whereYear('penalty_date', $thisMonth->year)
            ->distinct()
            ->pluck('rake_id');

        $totalRakesThisMonth = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('dispatch_time')
            ->whereMonth('dispatch_time', $thisMonth->month)
            ->whereYear('dispatch_time', $thisMonth->year)
            ->count();

        $rakesWithPenalties = $rakeIdsWithPenalties->count();
        $rakesWithinFreeTime = max(0, $totalRakesThisMonth - $rakesWithPenalties);

        $moneyLost = (float) Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('penalty_date', $thisMonth->month)
            ->whereYear('penalty_date', $thisMonth->year)
            ->sum('penalty_amount');

        // Estimate money saved: average penalty per penalised rake × rakes without penalties
        $avgPenaltyPerRake = $rakesWithPenalties > 0 ? $moneyLost / $rakesWithPenalties : 0;
        $moneySaved = $rakesWithinFreeTime * $avgPenaltyPerRake;

        return [
            'rakes_within_free_time' => $rakesWithinFreeTime,
            'rakes_with_penalties' => $rakesWithPenalties,
            'money_saved' => round($moneySaved, 2),
            'money_lost' => round($moneyLost, 2),
        ];
    }

    /**
     * Financial impact summary: YTD totals, projected annual, cost per rake, worst siding.
     *
     * @param  array<int>  $sidingIds
     * @return array{ytd_total: float, projected_annual: float, cost_per_rake: float, worst_siding: string|null, trend_direction: string}
     */
    private function buildFinancialImpact(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return ['ytd_total' => 0, 'projected_annual' => 0, 'cost_per_rake' => 0, 'worst_siding' => null, 'trend_direction' => 'flat'];
        }

        $startOfYear = now()->startOfYear();

        $ytdTotal = (float) Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', $startOfYear)
            ->sum('penalty_amount');

        $monthsElapsed = max(1, now()->month);
        $projectedAnnual = round(($ytdTotal / $monthsElapsed) * 12, 2);

        $ytdRakesWithPenalties = Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', $startOfYear)
            ->distinct()
            ->count('rake_id');

        $costPerRake = $ytdRakesWithPenalties > 0 ? round($ytdTotal / $ytdRakesWithPenalties, 2) : 0;

        // Worst siding
        $worstSiding = Penalty::query()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->where('penalty_date', '>=', $startOfYear)
            ->selectRaw('sidings.name, sum(penalty_amount) as total')
            ->groupBy('sidings.name')
            ->orderByDesc('total')
            ->value('name');

        // Trend: compare this month to last month
        $thisMonthTotal = (float) Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('penalty_date', now()->month)
            ->whereYear('penalty_date', now()->year)
            ->sum('penalty_amount');

        $lastMonthTotal = (float) Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->whereMonth('penalty_date', now()->subMonth()->month)
            ->whereYear('penalty_date', now()->subMonth()->year)
            ->sum('penalty_amount');

        $trendDirection = $thisMonthTotal > $lastMonthTotal ? 'up' : ($thisMonthTotal < $lastMonthTotal ? 'down' : 'flat');

        return [
            'ytd_total' => round($ytdTotal, 2),
            'projected_annual' => $projectedAnnual,
            'cost_per_rake' => $costPerRake,
            'worst_siding' => $worstSiding,
            'trend_direction' => $trendDirection,
        ];
    }

    /**
     * Rake distribution by state for donut chart.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, value: int}>
     */
    private function buildRakeStateChart(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        return Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->selectRaw('state as name, count(*) as value')
            ->groupBy('state')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($r): array => [
                'name' => ucfirst(str_replace('_', ' ', (string) $r->name)),
                'value' => (int) $r->value,
            ])
            ->values()
            ->all();
    }

    /**
     * Indent pipeline breakdown by state.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, value: int}>
     */
    private function buildIndentPipeline(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        return Indent::query()
            ->whereIn('siding_id', $sidingIds)
            ->selectRaw('state as name, count(*) as value')
            ->groupBy('state')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($r): array => [
                'name' => ucfirst(str_replace('_', ' ', (string) $r->name)),
                'value' => (int) $r->value,
            ])
            ->values()
            ->all();
    }

    /**
     * Penalty status breakdown (paid, disputed, waived, pending, etc.).
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, value: float, count: int}>
     */
    private function buildPenaltyStatusBreakdown(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        return Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12))
            ->selectRaw('penalty_status as name, sum(penalty_amount) as value, count(*) as count')
            ->groupBy('penalty_status')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($r): array => [
                'name' => ucfirst((string) $r->name),
                'value' => (float) $r->value,
                'count' => (int) $r->count,
            ])
            ->values()
            ->all();
    }

    /**
     * Penalties by responsible party for bar chart.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, value: float, count: int}>
     */
    private function buildResponsiblePartyBreakdown(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        return Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12))
            ->whereNotNull('responsible_party')
            ->selectRaw('responsible_party as name, sum(penalty_amount) as value, count(*) as count')
            ->groupBy('responsible_party')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($r): array => [
                'name' => ucfirst((string) $r->name),
                'value' => (float) $r->value,
                'count' => (int) $r->count,
            ])
            ->values()
            ->all();
    }

    /**
     * Per-siding performance: rakes handled, penalties incurred, penalty rate.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, rakes: int, penalties: int, penalty_amount: float, penalty_rate: float}>
     */
    private function buildSidingPerformance(array $sidingIds, CarbonInterface $from, CarbonInterface $to): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $rakesBySiding = Rake::query()
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->whereBetween('rakes.created_at', [$from, $to])
            ->selectRaw('sidings.name, count(*) as total_rakes')
            ->groupBy('sidings.name')
            ->pluck('total_rakes', 'name')
            ->all();

        $penaltiesBySiding = Penalty::query()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->whereBetween('penalty_date', [$from, $to])
            ->selectRaw('sidings.name, count(*) as penalty_count, count(DISTINCT penalties.rake_id) as penalised_rakes, sum(penalty_amount) as penalty_total')
            ->groupBy('sidings.name')
            ->get()
            ->keyBy('name');

        $result = [];
        foreach ($rakesBySiding as $name => $rakeCount) {
            $penaltyData = $penaltiesBySiding->get($name);
            $penaltyCount = $penaltyData ? (int) $penaltyData->penalty_count : 0;
            $penalisedRakes = $penaltyData ? (int) $penaltyData->penalised_rakes : 0;
            $penaltyAmount = $penaltyData ? (float) $penaltyData->penalty_total : 0.0;
            $result[] = [
                'name' => (string) $name,
                'rakes' => (int) $rakeCount,
                'penalties' => $penaltyCount,
                'penalty_amount' => round($penaltyAmount, 2),
                'penalty_rate' => $rakeCount > 0 ? round(($penalisedRakes / $rakeCount) * 100, 1) : 0,
            ];
        }

        usort($result, fn ($a, $b) => $b['penalty_amount'] <=> $a['penalty_amount']);

        return $result;
    }

    /**
     * Undisputed penalties that represent a savings opportunity.
     *
     * @param  array<int>  $sidingIds
     * @return array{potential_savings: float, undisputed_count: int}
     */
    private function buildDisputeOpportunity(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return ['potential_savings' => 0, 'undisputed_count' => 0];
        }

        $baseQuery = fn () => Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12));

        $undisputed = $baseQuery()
            ->whereIn('penalty_status', ['incurred', 'pending'])
            ->whereNull('disputed_at')
            ->selectRaw('count(*) as count, sum(penalty_amount) as total')
            ->first();

        $undisputedCount = (int) ($undisputed->count ?? 0);
        $undisputedAmount = (float) ($undisputed->total ?? 0);

        // Apply estimated success rate
        $disputedTotal = $baseQuery()->whereIn('penalty_status', ['disputed', 'waived'])->count();
        $waivedCount = $baseQuery()->where('penalty_status', 'waived')->count();
        $successRate = $disputedTotal > 0 ? $waivedCount / $disputedTotal : 0.3;

        return [
            'potential_savings' => round($undisputedAmount * $successRate, 2),
            'undisputed_count' => $undisputedCount,
        ];
    }

    /**
     * Date-wise rail dispatch and penalty amounts broken down by siding.
     *
     * @param  array<int>  $sidingIds
     * @return array{sidingNames: array<int, string>, dates: list<array<string, mixed>>}
     */
    private function buildDateWiseDispatch(array $sidingIds, CarbonInterface $from, CarbonInterface $to): array
    {
        $sidingMap = Siding::query()
            ->whereIn('id', $sidingIds)
            ->pluck('name', 'id')
            ->all();

        $days = [];
        $cursor = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();
        while ($cursor->lte($end)) {
            $d = $cursor->format('Y-m-d');
            $row = ['date' => $cursor->format('d M'), 'total_dispatched' => 0, 'total_penalty' => 0.0];
            foreach ($sidingMap as $id => $name) {
                $row["dispatched_{$id}"] = 0;
                $row["penalty_{$id}"] = 0.0;
            }
            $days[$d] = $row;
            $cursor->addDay();
        }

        if ($sidingIds === [] || $days === []) {
            return ['sidingNames' => $sidingMap, 'dates' => array_values($days)];
        }

        $driver = DB::getDriverName();
        $dateSql = $driver === 'pgsql' ? 'dispatch_time::date' : 'DATE(dispatch_time)';

        $dispatched = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('dispatch_time')
            ->whereBetween('dispatch_time', [$from, $to])
            ->selectRaw("{$dateSql} as d, siding_id, count(*) as cnt")
            ->groupBy('d', 'siding_id')
            ->get();

        foreach ($dispatched as $row) {
            $d = $row->d;
            if (isset($days[$d])) {
                $days[$d]["dispatched_{$row->siding_id}"] = (int) $row->cnt;
                $days[$d]['total_dispatched'] += (int) $row->cnt;
            }
        }

        $penDateSql = $driver === 'pgsql' ? 'penalty_date::date' : 'DATE(penalty_date)';

        $penalties = Penalty::query()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->whereBetween('penalties.penalty_date', [$from, $to])
            ->selectRaw("{$penDateSql} as d, rakes.siding_id, sum(penalties.penalty_amount) as total")
            ->groupBy('d', 'rakes.siding_id')
            ->get();

        foreach ($penalties as $row) {
            $d = $row->d;
            if (isset($days[$d])) {
                $days[$d]["penalty_{$row->siding_id}"] = round((float) $row->total, 2);
                $days[$d]['total_penalty'] += round((float) $row->total, 2);
            }
        }

        return ['sidingNames' => $sidingMap, 'dates' => array_values($days)];
    }

    /**
     * Rake-wise performance: recent dispatched rakes with key metrics.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array<string, mixed>>
     */
    private function buildRakePerformance(array $sidingIds, CarbonInterface $from, CarbonInterface $to): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $rakes = Rake::query()
            ->with('siding:id,name,code')
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('dispatch_time')
            ->whereBetween('dispatch_time', [$from, $to])
            ->orderByDesc('dispatch_time')
            ->limit(50)
            ->get();

        $rakeIds = $rakes->pluck('id')->all();

        $weighmentTotals = RakeWeighment::query()
            ->whereIn('rake_id', $rakeIds)
            ->selectRaw('rake_id, max(total_net_weight_mt) as net_weight, max(total_over_load_mt) as over_load, max(total_under_load_mt) as under_load')
            ->groupBy('rake_id')
            ->get()
            ->keyBy('rake_id');

        $penaltyTotals = Penalty::query()
            ->whereIn('rake_id', $rakeIds)
            ->selectRaw('rake_id, sum(penalty_amount) as total_penalty, count(*) as penalty_count')
            ->groupBy('rake_id')
            ->get()
            ->keyBy('rake_id');

        return $rakes->map(function (Rake $rake) use ($weighmentTotals, $penaltyTotals): array {
            $w = $weighmentTotals->get($rake->id);
            $p = $penaltyTotals->get($rake->id);

            $loadingMinutes = null;
            if ($rake->loading_start_time && $rake->loading_end_time) {
                $loadingMinutes = (int) $rake->loading_start_time->diffInMinutes($rake->loading_end_time);
            }

            return [
                'rake_number' => $rake->rake_number,
                'siding' => $rake->siding?->name ?? '—',
                'dispatch_date' => $rake->dispatch_time->format('d M Y'),
                'wagon_count' => $rake->wagon_count,
                'net_weight' => $w ? round((float) $w->net_weight, 2) : null,
                'over_load' => $w ? round((float) $w->over_load, 2) : null,
                'under_load' => $w ? round((float) $w->under_load, 2) : null,
                'loading_minutes' => $loadingMinutes,
                'penalty_amount' => $p ? round((float) $p->total_penalty, 2) : 0,
                'penalty_count' => $p ? (int) $p->penalty_count : 0,
            ];
        })->values()->all();
    }

    /**
     * Loader-wise overloading trends (last 6 months, monthly).
     *
     * @param  array<int>  $sidingIds
     * @return array{loaders: array<int, array{id: int, name: string, siding: string}>, monthly: array<int, array<string, mixed>>}
     */
    private function buildLoaderOverloadTrends(array $sidingIds, CarbonInterface $from, CarbonInterface $to): array
    {
        if ($sidingIds === []) {
            return ['loaders' => [], 'monthly' => []];
        }

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

        $driver = DB::getDriverName();
        $yearMonthSql = $driver === 'pgsql'
            ? 'EXTRACT(YEAR FROM wl.loading_time)::int as y, EXTRACT(MONTH FROM wl.loading_time)::int as m'
            : 'YEAR(wl.loading_time) as y, MONTH(wl.loading_time) as m';

        $rows = DB::table('wagon_loading as wl')
            ->join('rake_wagon_weighments as rww', function ($join) {
                $join->on('wl.wagon_id', '=', 'rww.wagon_id')
                    ->on('wl.rake_id', '=', DB::raw('(SELECT rake_id FROM rake_weighments WHERE id = rww.rake_weighment_id)'));
            })
            ->whereIn('wl.loader_id', $loaderIds)
            ->whereNotNull('wl.loading_time')
            ->whereBetween('wl.loading_time', [$from, $to])
            ->selectRaw("{$yearMonthSql}, wl.loader_id, count(*) as wagons_loaded, sum(CASE WHEN rww.over_load_mt > 0 THEN 1 ELSE 0 END) as overloaded_wagons")
            ->groupBy('y', 'm', 'wl.loader_id')
            ->get();

        $months = [];
        $cursor = Carbon::parse($from)->startOfMonth();
        $endMonth = Carbon::parse($to)->startOfMonth();
        while ($cursor->lte($endMonth)) {
            $key = $cursor->format('Y-m');
            $months[$key] = ['month' => $cursor->format('M Y')];
            foreach ($loaderMap as $id => $name) {
                $months[$key]["loader_{$id}_overload"] = 0;
                $months[$key]["loader_{$id}_total"] = 0;
            }
            $cursor->addMonth();
        }

        foreach ($rows as $r) {
            $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            if (isset($months[$key])) {
                $months[$key]["loader_{$r->loader_id}_overload"] = (int) $r->overloaded_wagons;
                $months[$key]["loader_{$r->loader_id}_total"] = (int) $r->wagons_loaded;
            }
        }

        return [
            'loaders' => $loaders->map(fn (Loader $l): array => [
                'id' => $l->id,
                'name' => $l->loader_name,
                'siding' => $l->siding?->name ?? '—',
            ])->values()->all(),
            'monthly' => array_values($months),
        ];
    }

    /**
     * Power plant wise dispatch: rakes dispatched and weight received per power plant.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, rakes: int, weight_mt: float, avg_variance_pct: float}>
     */
    private function buildPowerPlantDispatch(array $sidingIds, CarbonInterface $from, CarbonInterface $to): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $rakeIds = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('dispatch_time')
            ->whereBetween('dispatch_time', [$from, $to])
            ->pluck('id')
            ->all();

        if ($rakeIds === []) {
            return [];
        }

        return PowerPlantReceipt::query()
            ->join('power_plants', 'power_plant_receipts.power_plant_id', '=', 'power_plants.id')
            ->whereIn('power_plant_receipts.rake_id', $rakeIds)
            ->selectRaw('power_plants.name, count(*) as rakes, sum(power_plant_receipts.weight_mt) as weight_mt, avg(power_plant_receipts.variance_pct) as avg_variance_pct')
            ->groupBy('power_plants.name')
            ->orderByDesc('rakes')
            ->get()
            ->map(fn ($r): array => [
                'name' => (string) $r->name,
                'rakes' => (int) $r->rakes,
                'weight_mt' => round((float) $r->weight_mt, 2),
                'avg_variance_pct' => round((float) $r->avg_variance_pct, 2),
            ])
            ->values()
            ->all();
    }

    /**
     * Siding-wise monthly breakdown (rakes dispatched, penalties, overload) for stacked comparison.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array<string, mixed>>
     */
    private function buildSidingWiseMonthly(array $sidingIds, CarbonInterface $from, CarbonInterface $to): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $sidingNames = Siding::query()
            ->whereIn('id', $sidingIds)
            ->pluck('name', 'id')
            ->all();

        $driver = DB::getDriverName();
        $yearMonthSql = $driver === 'pgsql'
            ? 'EXTRACT(YEAR FROM dispatch_time)::int as y, EXTRACT(MONTH FROM dispatch_time)::int as m'
            : 'YEAR(dispatch_time) as y, MONTH(dispatch_time) as m';

        $rakeRows = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('dispatch_time')
            ->whereBetween('dispatch_time', [$from, $to])
            ->selectRaw("{$yearMonthSql}, siding_id, count(*) as cnt")
            ->groupBy('y', 'm', 'siding_id')
            ->get();

        $months = [];
        $cursor = Carbon::parse($from)->startOfMonth();
        $endMonth = Carbon::parse($to)->startOfMonth();
        while ($cursor->lte($endMonth)) {
            $key = $cursor->format('Y-m');
            $entry = ['month' => $cursor->format('M Y')];
            foreach ($sidingNames as $name) {
                $entry[$name] = 0;
            }
            $months[$key] = $entry;
            $cursor->addMonth();
        }

        foreach ($rakeRows as $r) {
            $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            $name = $sidingNames[$r->siding_id] ?? null;
            if ($name && isset($months[$key])) {
                $months[$key][$name] = (int) $r->cnt;
            }
        }

        return array_values($months);
    }

    /**
     * Siding radar data for multi-dimensional comparison (normalized 0-100).
     *
     * @param  array<int>  $sidingIds
     * @return array{dimensions: array<int, array{dimension: string}>, sidingKeys: array<string>}
     */
    /**
     * Per-siding comparison with actual values for each metric.
     *
     * @param  array<int>  $sidingIds
     * @return array<string, array<int, array{name: string, rakes_dispatched: int, on_time_pct: float, vehicles: int, penalty_amount: float}>>
     */
    private function buildSidingRadar(array $sidingIds, CarbonInterface $from, CarbonInterface $to): array
    {
        if ($sidingIds === []) {
            return ['sidings' => []];
        }

        $sidingNames = Siding::query()
            ->whereIn('id', $sidingIds)
            ->pluck('name', 'id')
            ->all();

        $dispatchedBySiding = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereNotNull('dispatch_time')
            ->whereBetween('dispatch_time', [$from, $to])
            ->selectRaw('siding_id, count(*) as cnt')
            ->groupBy('siding_id')
            ->pluck('cnt', 'siding_id')
            ->all();

        $rakesBySiding = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('siding_id, count(*) as cnt')
            ->groupBy('siding_id')
            ->pluck('cnt', 'siding_id')
            ->all();

        $penaltyBySiding = Penalty::query()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->whereBetween('penalty_date', [$from, $to])
            ->selectRaw('rakes.siding_id, count(DISTINCT penalties.rake_id) as penalised_rakes, sum(penalty_amount) as total')
            ->groupBy('rakes.siding_id')
            ->get()
            ->keyBy('siding_id');

        $vehiclesBySiding = VehicleUnload::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('siding_id, count(*) as cnt')
            ->groupBy('siding_id')
            ->pluck('cnt', 'siding_id')
            ->all();

        $result = [];
        foreach ($sidingIds as $sid) {
            $name = $sidingNames[$sid] ?? "Siding {$sid}";
            $dispatched = (int) ($dispatchedBySiding[$sid] ?? 0);
            $totalRakes = (int) ($rakesBySiding[$sid] ?? 0);
            $penalisedRakes = (int) ($penaltyBySiding->get($sid)?->penalised_rakes ?? 0);
            $penaltyTotal = round((float) ($penaltyBySiding->get($sid)?->total ?? 0), 2);
            $vehicles = (int) ($vehiclesBySiding[$sid] ?? 0);
            $onTimeCount = max(0, $totalRakes - $penalisedRakes);

            $result[] = [
                'name' => $name,
                'rakes_dispatched' => $dispatched,
                'on_time' => $onTimeCount,
                'vehicles' => $vehicles,
                'penalty_amount' => $penaltyTotal,
            ];
        }

        return ['sidings' => $result];
    }

    /**
     * Loader overload rate gauges for radial chart.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, value: float}>
     */
    private function buildLoaderGauges(array $sidingIds, CarbonInterface $from, CarbonInterface $to): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $loaders = Loader::query()
            ->whereIn('siding_id', $sidingIds)
            ->with('siding:id,name')
            ->get(['id', 'loader_name', 'siding_id']);

        if ($loaders->isEmpty()) {
            return [];
        }

        $loaderIds = $loaders->pluck('id')->all();

        $stats = DB::table('wagon_loading as wl')
            ->join('rake_wagon_weighments as rww', function ($join) {
                $join->on('wl.wagon_id', '=', 'rww.wagon_id')
                    ->on('wl.rake_id', '=', DB::raw('(SELECT rake_id FROM rake_weighments WHERE id = rww.rake_weighment_id)'));
            })
            ->whereIn('wl.loader_id', $loaderIds)
            ->whereNotNull('wl.loading_time')
            ->whereBetween('wl.loading_time', [$from, $to])
            ->selectRaw('wl.loader_id, count(*) as total, sum(CASE WHEN rww.over_load_mt > 0 THEN 1 ELSE 0 END) as overloaded')
            ->groupBy('wl.loader_id')
            ->get()
            ->keyBy('loader_id');

        return $loaders->map(function (Loader $l) use ($stats): array {
            $s = $stats->get($l->id);
            $total = $s ? (int) $s->total : 0;
            $overloaded = $s ? (int) $s->overloaded : 0;
            $rate = $total > 0 ? round(($overloaded / $total) * 100, 1) : 0;

            return [
                'name' => $l->loader_name,
                'value' => $rate,
            ];
        })->values()->all();
    }
}

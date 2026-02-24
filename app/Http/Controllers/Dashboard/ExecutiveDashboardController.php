<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Actions\GenerateDailyBriefingAction;
use App\Actions\SyncDemurrageAlertsAction;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\CoalStock;
use App\Models\Indent;
use App\Models\Penalty;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\StockLedger;
use App\Models\VehicleUnload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class ExecutiveDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $summary = $this->buildSummary($sidingIds);
        $sidingStocks = $this->buildSidingStocks($sidingIds);
        $activeRakes = $this->buildActiveRakes($sidingIds);
        resolve(SyncDemurrageAlertsAction::class)->handle($sidingIds);
        $alerts = $this->buildAlerts($sidingIds);
        $penaltyChartData = $this->buildPenaltyChartData($sidingIds);
        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Siding $s): array => ['id' => $s->id, 'name' => $s->name, 'code' => $s->code]);

        $penaltyByType = $this->buildPenaltyByType($sidingIds);
        $penaltyBySiding = $this->buildPenaltyBySiding($sidingIds);
        $costAvoidance = $this->buildCostAvoidance($sidingIds);
        $financialImpact = $this->buildFinancialImpact($sidingIds);
        $rakeStateChart = $this->buildRakeStateChart($sidingIds);
        $indentPipeline = $this->buildIndentPipeline($sidingIds);
        $penaltyStatusBreakdown = $this->buildPenaltyStatusBreakdown($sidingIds);
        $responsiblePartyBreakdown = $this->buildResponsiblePartyBreakdown($sidingIds);
        $sidingPerformance = $this->buildSidingPerformance($sidingIds);
        $disputeOpportunity = $this->buildDisputeOpportunity($sidingIds);

        return Inertia::render('dashboard', [
            'summary' => $summary,
            'sidingStocks' => $sidingStocks,
            'activeRakes' => $activeRakes,
            'alerts' => $alerts,
            'penaltyChartData' => $penaltyChartData,
            'penaltyByType' => $penaltyByType,
            'penaltyBySiding' => $penaltyBySiding,
            'costAvoidance' => $costAvoidance,
            'financialImpact' => $financialImpact,
            'rakeStateChart' => $rakeStateChart,
            'indentPipeline' => $indentPipeline,
            'penaltyStatusBreakdown' => $penaltyStatusBreakdown,
            'responsiblePartyBreakdown' => $responsiblePartyBreakdown,
            'sidingPerformance' => $sidingPerformance,
            'disputeOpportunity' => $disputeOpportunity,
            'sidings' => $sidings,
            'aiBriefing' => Inertia::defer(fn (): ?string => resolve(GenerateDailyBriefingAction::class)->handle($user, $sidingIds)),
        ]);
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
            ->whereNotNull('free_time_minutes')
            ->get();

        $list = [];
        foreach ($rakes as $rake) {
            $start = $rake->placement_time;
            $freeMinutes = (int) $rake->free_time_minutes;
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
    private function buildSidingPerformance(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $rakesBySiding = Rake::query()
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->where('rakes.created_at', '>=', now()->subMonths(12))
            ->selectRaw('sidings.name, count(*) as total_rakes')
            ->groupBy('sidings.name')
            ->pluck('total_rakes', 'name')
            ->all();

        $penaltiesBySiding = Penalty::query()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->where('penalty_date', '>=', now()->subMonths(12))
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
}

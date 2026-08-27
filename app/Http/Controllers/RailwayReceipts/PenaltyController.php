<?php

declare(strict_types=1);

namespace App\Http\Controllers\RailwayReceipts;

use App\Actions\BuildPenaltyChartDataAction;
use App\Actions\GeneratePenaltyInsightsAction;
use App\Actions\RecommendDisputeAction;
use App\DataTables\PenaltyDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePenaltyRequest;
use App\Models\Penalty;
use App\Models\Siding;
use App\Support\PenaltyDateFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class PenaltyController extends Controller
{
    public function index(Request $request): Response
    {
        $sidingIds = $this->sidingIdsForUser($request);

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $chartData = resolve(BuildPenaltyChartDataAction::class)->handle($request);

        return Inertia::render('penalties/index', [
            'tableData' => PenaltyDataTable::makeTable($request),
            'chartData' => $chartData,
            'sidings' => $sidings,
            'demurrage_rate_per_mt_hour' => config('rrmcs.demurrage_rate_per_mt_hour', 50),
        ]);
    }

    public function update(UpdatePenaltyRequest $request, Penalty $penalty): RedirectResponse
    {
        $penalty->update($request->validated());

        return back();
    }

    public function analytics(Request $request): Response
    {
        $sidingIds = $this->sidingIdsForUser($request);

        $summaryCards = $this->buildAnalyticsSummary($sidingIds, $request);
        $byResponsibleParty = $this->buildByResponsibleParty();
        $byType = $this->buildByType($sidingIds, $request);
        $bySiding = $this->buildBySiding($sidingIds, $request);
        $monthlyTrend = $this->buildMonthlyTrend($sidingIds, $request);
        $topOffenders = $this->buildTopOffenders($sidingIds, $request);
        $weekdayHeatmap = $this->buildWeekdayHeatmap($sidingIds, $request);
        $rootCauseBreakdown = $this->buildRootCauseBreakdown($sidingIds);
        $disputeAnalysis = $this->buildDisputeAnalysis($sidingIds);
        $penaltyTypeTrend = $this->buildPenaltyTypeTrend($sidingIds);
        $costSavingOpportunities = $this->buildCostSavingOpportunities($sidingIds);
        $responsiblePartyDetail = $this->buildResponsiblePartyDetail($sidingIds);
        $byOperator = $this->buildByOperator($sidingIds, $request);
        $byShift = $this->buildByShift($sidingIds, $request);
        $byWagonType = $this->buildByWagonType($sidingIds, $request);
        $operatorLeaderboard = $this->buildOperatorLeaderboard($sidingIds);

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('penalties/analytics', [
            'summaryCards' => $summaryCards,
            'byResponsibleParty' => $byResponsibleParty,
            'byType' => $byType,
            'bySiding' => $bySiding,
            'monthlyTrend' => $monthlyTrend,
            'topOffenders' => $topOffenders,
            'weekdayHeatmap' => $weekdayHeatmap,
            'rootCauseBreakdown' => $rootCauseBreakdown,
            'disputeAnalysis' => $disputeAnalysis,
            'penaltyTypeTrend' => $penaltyTypeTrend,
            'costSavingOpportunities' => $costSavingOpportunities,
            'responsiblePartyDetail' => $responsiblePartyDetail,
            'byOperator' => $byOperator,
            'byShift' => $byShift,
            'byWagonType' => $byWagonType,
            'operatorLeaderboard' => $operatorLeaderboard,
            'sidings' => $sidings,
            'aiInsights' => Inertia::defer(fn () => resolve(GeneratePenaltyInsightsAction::class)->handle($sidingIds)),
        ]);
    }

    /**
     * Get AI dispute recommendation for a penalty.
     */
    public function disputeRecommendation(Penalty $penalty, RecommendDisputeAction $action): JsonResponse
    {
        $recommendation = $action->handle($penalty);

        if ($recommendation === null) {
            return response()->json([
                'error' => 'Unable to generate dispute recommendation. AI service may be unavailable.',
            ], 503);
        }

        return response()->json($recommendation);
    }

    /**
     * @return array<int>
     */
    private function sidingIdsForUser(Request $request): array
    {
        $user = $request->user();

        return $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();
    }

    /**
     * Base query joining rr_penalty_snapshots to rr_documents/rakes/sidings/penalty_types,
     * scoped to the given sidings, honoring `filter[penalty_date]` (same convention as
     * PenaltyDataTable / BuildPenaltyChartDataAction) with a 12-month default window.
     *
     * @param  array<int>  $sidingIds
     */
    private function penaltySnapshotQuery(array $sidingIds, Request $request): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('rr_penalty_snapshots')
            ->leftJoin('rr_documents', 'rr_penalty_snapshots.rr_document_id', '=', 'rr_documents.id')
            ->join('rakes', 'rr_penalty_snapshots.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->leftJoin('penalty_types', 'rr_penalty_snapshots.penalty_code', '=', 'penalty_types.code')
            ->whereIn('rakes.siding_id', $sidingIds);

        $filters = $request->get('filter', []);
        if (isset($filters['penalty_date'])) {
            PenaltyDateFilter::apply($query, $filters['penalty_date']);
        } else {
            $query->whereRaw(PenaltyDateFilter::DATE_EXPR.' >= ?', [now()->subMonths(12)]);
        }

        return $query;
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<string, mixed>
     */
    private function buildAnalyticsSummary(array $sidingIds, Request $request): array
    {
        if ($sidingIds === []) {
            return [
                'total_penalties' => 0,
                'total_amount' => 0,
                'by_status' => [],
                'disputed_count' => 0,
                'waived_count' => 0,
                'dispute_success_rate' => 0,
                'avg_penalty' => 0,
            ];
        }

        $total = (int) $this->penaltySnapshotQuery($sidingIds, $request)->count();
        $totalAmount = (float) $this->penaltySnapshotQuery($sidingIds, $request)->sum('rr_penalty_snapshots.amount');

        // Dispute/status workflow (by_status, disputed_count, waived_count, dispute_success_rate)
        // has no equivalent on rr_penalty_snapshots — reported as empty/zero rather than fabricated.
        return [
            'total_penalties' => $total,
            'total_amount' => round($totalAmount, 2),
            'by_status' => [],
            'disputed_count' => 0,
            'waived_count' => 0,
            'dispute_success_rate' => 0,
            'avg_penalty' => $total > 0 ? round($totalAmount / $total, 2) : 0,
        ];
    }

    /**
     * responsible_party has no equivalent on rr_penalty_snapshots — the frontend already
     * renders a helpful empty state for this list, so it's reported empty rather than faked.
     *
     * @return array<int, array{name: string, value: float, count: int}>
     */
    private function buildByResponsibleParty(): array
    {
        return [];
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, value: float, count: int}>
     */
    private function buildByType(array $sidingIds, Request $request): array
    {
        if ($sidingIds === []) {
            return [];
        }

        return $this->penaltySnapshotQuery($sidingIds, $request)
            ->selectRaw('rr_penalty_snapshots.penalty_code as name, sum(rr_penalty_snapshots.amount) as value, count(*) as count')
            ->groupBy('rr_penalty_snapshots.penalty_code')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($r): array => [
                'name' => (string) $r->name,
                'value' => (float) $r->value,
                'count' => (int) $r->count,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, total: float, count: int, types: array<string, float>}>
     */
    private function buildBySiding(array $sidingIds, Request $request): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $rows = $this->penaltySnapshotQuery($sidingIds, $request)
            ->selectRaw('sidings.name as siding_name, rr_penalty_snapshots.penalty_code, sum(rr_penalty_snapshots.amount) as total, count(*) as count')
            ->groupBy('sidings.name', 'rr_penalty_snapshots.penalty_code')
            ->orderByDesc('total')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $name = (string) $row->siding_name;
            if (! isset($grouped[$name])) {
                $grouped[$name] = ['name' => $name, 'total' => 0, 'count' => 0, 'types' => []];
            }
            $grouped[$name]['total'] += (float) $row->total;
            $grouped[$name]['count'] += (int) $row->count;
            $grouped[$name]['types'][$row->penalty_code] = (float) $row->total;
        }

        usort($grouped, fn ($a, $b) => $b['total'] <=> $a['total']);

        return array_values($grouped);
    }

    /**
     * Monthly trend for last 12 months (backfilled with zeros).
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{month: string, total: float, count: int}>
     */
    private function buildMonthlyTrend(array $sidingIds, Request $request): array
    {
        // Build all 12 months with zeros as baseline
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
        $dateExpr = PenaltyDateFilter::DATE_EXPR;
        $yearMonthSql = match ($driver) {
            'pgsql' => "EXTRACT(YEAR FROM ({$dateExpr}))::int as y, EXTRACT(MONTH FROM ({$dateExpr}))::int as m",
            'sqlite' => "CAST(strftime('%Y', {$dateExpr}) AS INTEGER) as y, CAST(strftime('%m', {$dateExpr}) AS INTEGER) as m",
            default => "YEAR({$dateExpr}) as y, MONTH({$dateExpr}) as m",
        };

        $rows = $this->penaltySnapshotQuery($sidingIds, $request)
            ->selectRaw("{$yearMonthSql}, sum(rr_penalty_snapshots.amount) as total, count(*) as count")
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
     * Top 10 rakes/sidings with highest cumulative penalties.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{rake_number: string, siding_name: string, total: float, count: int, types: string}>
     */
    private function buildTopOffenders(array $sidingIds, Request $request): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $driver = DB::getDriverName();
        $groupConcatSql = $driver === 'pgsql'
            ? "STRING_AGG(DISTINCT rr_penalty_snapshots.penalty_code, ',')"
            : 'GROUP_CONCAT(DISTINCT rr_penalty_snapshots.penalty_code)';

        return $this->penaltySnapshotQuery($sidingIds, $request)
            ->selectRaw("rakes.rake_number, sidings.name as siding_name, sum(rr_penalty_snapshots.amount) as total, count(*) as count, {$groupConcatSql} as types")
            ->groupBy('rakes.rake_number', 'sidings.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r): array => [
                'rake_number' => (string) $r->rake_number,
                'siding_name' => (string) $r->siding_name,
                'total' => (float) $r->total,
                'count' => (int) $r->count,
                'types' => (string) $r->types,
            ])
            ->values()
            ->all();
    }

    /**
     * Penalties by day of week (0=Sun..6=Sat) for heatmap.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{day: int, count: int, total: float}>
     */
    private function buildWeekdayHeatmap(array $sidingIds, Request $request): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $driver = DB::getDriverName();
        $dateExpr = PenaltyDateFilter::DATE_EXPR;
        $dowSql = match ($driver) {
            'pgsql' => "EXTRACT(DOW FROM ({$dateExpr}))::int as day",
            'sqlite' => "CAST(strftime('%w', {$dateExpr}) AS INTEGER) as day",
            default => "DAYOFWEEK({$dateExpr}) - 1 as day",
        };

        return $this->penaltySnapshotQuery($sidingIds, $request)
            ->selectRaw("{$dowSql}, count(*) as count, sum(rr_penalty_snapshots.amount) as total")
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($r): array => [
                'day' => (int) $r->day,
                'count' => (int) $r->count,
                'total' => (float) $r->total,
            ])
            ->values()
            ->all();
    }

    /**
     * Root cause breakdown using keyword-based categorisation.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{category: string, count: int, total: float}>
     */
    private function buildRootCauseBreakdown(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $driver = DB::getDriverName();
        $like = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';

        $caseSql = "CASE
            WHEN root_cause {$like} '%equipment%' OR root_cause {$like} '%breakdown%' OR root_cause {$like} '%malfunction%' THEN 'Equipment Failure'
            WHEN root_cause {$like} '%labour%' OR root_cause {$like} '%crew%' OR root_cause {$like} '%absentee%' OR root_cause {$like} '%shift changeover%' THEN 'Labour Shortage'
            WHEN root_cause {$like} '%weather%' OR root_cause {$like} '%rain%' OR root_cause {$like} '%fog%' OR root_cause {$like} '%cyclone%' OR root_cause {$like} '%waterlog%' THEN 'Weather/Environmental'
            WHEN root_cause {$like} '%communication%' OR root_cause {$like} '%miscommuni%' OR root_cause {$like} '%not informed%' THEN 'Communication Gap'
            WHEN root_cause {$like} '%scheduling%' OR root_cause {$like} '%double booking%' OR root_cause {$like} '%not scheduled%' THEN 'Scheduling Error'
            WHEN root_cause {$like} '%documentation%' OR root_cause {$like} '%paperwork%' OR root_cause {$like} '%RR processing%' OR root_cause {$like} '%certificate%' THEN 'Documentation Delay'
            WHEN root_cause {$like} '%infrastructure%' OR root_cause {$like} '%track%' OR root_cause {$like} '%power outage%' OR root_cause {$like} '%signal failure%' THEN 'Infrastructure Issue'
            WHEN root_cause {$like} '%operational%' OR root_cause {$like} '%shunting%' OR root_cause {$like} '%clearance%' OR root_cause {$like} '%loading rate%' THEN 'Operational Delay'
            ELSE 'Other'
        END";

        return Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12))
            ->whereNotNull('root_cause')
            ->where('root_cause', '!=', '')
            ->selectRaw("{$caseSql} as category, count(*) as count, sum(penalty_amount) as total")
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r): array => [
                'category' => (string) $r->category,
                'count' => (int) $r->count,
                'total' => (float) $r->total,
            ])
            ->values()
            ->all();
    }

    /**
     * Dispute success analysis by penalty type and responsible party.
     *
     * @param  array<int>  $sidingIds
     * @return array{by_type: array<int, array<string, mixed>>, by_party: array<int, array<string, mixed>>, avg_resolution_days: float}
     */
    private function buildDisputeAnalysis(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return ['by_type' => [], 'by_party' => [], 'avg_resolution_days' => 0];
        }

        $baseQuery = fn () => Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12));

        // By penalty type: disputed vs waived counts
        $byType = $baseQuery()
            ->whereIn('penalty_status', ['disputed', 'waived'])
            ->selectRaw('penalty_type, penalty_status, count(*) as count, sum(penalty_amount) as total')
            ->groupBy('penalty_type', 'penalty_status')
            ->get();

        $typeMap = [];
        foreach ($byType as $row) {
            $type = (string) $row->penalty_type;
            if (! isset($typeMap[$type])) {
                $typeMap[$type] = ['type' => $type, 'disputed' => 0, 'waived' => 0, 'disputed_amount' => 0, 'waived_amount' => 0];
            }
            $typeMap[$type][$row->penalty_status] = (int) $row->count;
            $typeMap[$type][$row->penalty_status.'_amount'] = (float) $row->total;
        }

        $byTypeResult = array_values(array_map(function (array $item): array {
            $total = $item['disputed'] + $item['waived'];
            $item['success_rate'] = $total > 0 ? round(($item['waived'] / $total) * 100, 1) : 0;

            return $item;
        }, $typeMap));

        // By responsible party
        $byParty = $baseQuery()
            ->whereIn('penalty_status', ['disputed', 'waived'])
            ->whereNotNull('responsible_party')
            ->selectRaw('responsible_party, penalty_status, count(*) as count, sum(penalty_amount) as total')
            ->groupBy('responsible_party', 'penalty_status')
            ->get();

        $partyMap = [];
        foreach ($byParty as $row) {
            $party = ucfirst((string) $row->responsible_party);
            if (! isset($partyMap[$party])) {
                $partyMap[$party] = ['party' => $party, 'disputed' => 0, 'waived' => 0, 'waived_amount' => 0];
            }
            $partyMap[$party][$row->penalty_status] = (int) $row->count;
            if ($row->penalty_status === 'waived') {
                $partyMap[$party]['waived_amount'] = (float) $row->total;
            }
        }

        $byPartyResult = array_values(array_map(function (array $item): array {
            $total = $item['disputed'] + $item['waived'];
            $item['success_rate'] = $total > 0 ? round(($item['waived'] / $total) * 100, 1) : 0;

            return $item;
        }, $partyMap));

        // Average resolution days
        $driver = DB::getDriverName();
        $diffSql = $driver === 'pgsql'
            ? 'AVG(EXTRACT(EPOCH FROM (resolved_at - disputed_at)) / 86400)'
            : 'AVG(DATEDIFF(resolved_at, disputed_at))';

        $avgDays = (float) $baseQuery()
            ->whereNotNull('disputed_at')
            ->whereNotNull('resolved_at')
            ->selectRaw("{$diffSql} as avg_days")
            ->value('avg_days');

        return [
            'by_type' => $byTypeResult,
            'by_party' => $byPartyResult,
            'avg_resolution_days' => round($avgDays, 1),
        ];
    }

    /**
     * Monthly trend broken down by penalty type (multi-line chart).
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array<string, mixed>>
     */
    private function buildPenaltyTypeTrend(array $sidingIds): array
    {
        // Build month skeleton
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $months[$key] = ['month' => $date->format('M Y')];
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
            ->selectRaw("{$yearMonthSql}, penalty_type, sum(penalty_amount) as total")
            ->groupBy('y', 'm', 'penalty_type')
            ->get();

        // Collect all types
        $allTypes = $rows->pluck('penalty_type')->unique()->values()->all();

        // Initialize types to 0 in each month
        foreach ($months as &$month) {
            foreach ($allTypes as $type) {
                $month[$type] = 0;
            }
        }
        unset($month);

        foreach ($rows as $r) {
            $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            if (isset($months[$key])) {
                $months[$key][$r->penalty_type] = (float) $r->total;
            }
        }

        return array_values($months);
    }

    /**
     * Cost-saving opportunities from penalty data.
     *
     * @param  array<int>  $sidingIds
     * @return array{total_12m_spend: float, undisputed_count: int, undisputed_amount: float, projected_dispute_savings: float, root_cause_reduction_potential: float, siding_improvement_savings: float, total_potential_savings: float}
     */
    private function buildCostSavingOpportunities(array $sidingIds): array
    {
        $empty = [
            'total_12m_spend' => 0,
            'undisputed_count' => 0,
            'undisputed_amount' => 0,
            'projected_dispute_savings' => 0,
            'root_cause_reduction_potential' => 0,
            'siding_improvement_savings' => 0,
            'total_potential_savings' => 0,
        ];

        if ($sidingIds === []) {
            return $empty;
        }

        $baseQuery = fn () => Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12));

        $total12m = (float) $baseQuery()->sum('penalty_amount');

        // Undisputed penalties (incurred/pending but never disputed)
        $undisputed = $baseQuery()
            ->whereIn('penalty_status', ['incurred', 'pending'])
            ->whereNull('disputed_at')
            ->selectRaw('count(*) as count, sum(penalty_amount) as total')
            ->first();

        $undisputedCount = (int) ($undisputed->count ?? 0);
        $undisputedAmount = (float) ($undisputed->total ?? 0);

        // Projected dispute savings: apply current success rate to undisputed amount
        $disputedTotal = $baseQuery()->whereIn('penalty_status', ['disputed', 'waived'])->count();
        $waivedCount = $baseQuery()->where('penalty_status', 'waived')->count();
        $successRate = $disputedTotal > 0 ? $waivedCount / $disputedTotal : 0.3;
        $projectedDisputeSavings = round($undisputedAmount * $successRate, 2);

        // Root cause reduction: penalties with identified preventable causes
        $driver = DB::getDriverName();
        $like = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';

        $rootCauseAmount = (float) $baseQuery()
            ->whereNotNull('root_cause')
            ->where(function ($q) use ($like): void {
                $q->whereRaw("root_cause {$like} '%equipment%'")
                    ->orWhereRaw("root_cause {$like} '%labour%'")
                    ->orWhereRaw("root_cause {$like} '%scheduling%'")
                    ->orWhereRaw("root_cause {$like} '%communication%'");
            })
            ->sum('penalty_amount');

        // Assume 40% reduction potential for preventable causes
        $rootCauseReduction = round($rootCauseAmount * 0.4, 2);

        // Siding improvement: worst siding to median savings
        $sidingTotals = $baseQuery()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->selectRaw('rakes.siding_id, sum(penalty_amount) as total')
            ->groupBy('rakes.siding_id')
            ->pluck('total')
            ->sort()
            ->values()
            ->all();

        $sidingImprovement = 0.0;
        if (count($sidingTotals) >= 2) {
            $medianIdx = (int) floor(count($sidingTotals) / 2);
            $median = (float) $sidingTotals[$medianIdx];
            $worst = (float) end($sidingTotals);
            $sidingImprovement = round(max(0, $worst - $median), 2);
        }

        $totalPotential = round($projectedDisputeSavings + $rootCauseReduction + $sidingImprovement, 2);

        return [
            'total_12m_spend' => round($total12m, 2),
            'undisputed_count' => $undisputedCount,
            'undisputed_amount' => round($undisputedAmount, 2),
            'projected_dispute_savings' => $projectedDisputeSavings,
            'root_cause_reduction_potential' => $rootCauseReduction,
            'siding_improvement_savings' => $sidingImprovement,
            'total_potential_savings' => $totalPotential,
        ];
    }

    /**
     * Per responsible party with penalty type sub-distribution (for stacked chart).
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array<string, mixed>>
     */
    private function buildResponsiblePartyDetail(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $rows = Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12))
            ->whereNotNull('responsible_party')
            ->selectRaw('responsible_party, penalty_type, sum(penalty_amount) as total, count(*) as count')
            ->groupBy('responsible_party', 'penalty_type')
            ->get();

        $allTypes = $rows->pluck('penalty_type')->unique()->values()->all();

        $partyMap = [];
        foreach ($rows as $row) {
            $party = ucfirst((string) $row->responsible_party);
            if (! isset($partyMap[$party])) {
                $partyMap[$party] = ['party' => $party, 'total' => 0, 'count' => 0];
                foreach ($allTypes as $type) {
                    $partyMap[$party][$type] = 0;
                }
            }
            $partyMap[$party][$row->penalty_type] = (float) $row->total;
            $partyMap[$party]['total'] += (float) $row->total;
            $partyMap[$party]['count'] += (int) $row->count;
        }

        $result = array_values($partyMap);
        usort($result, fn ($a, $b) => $b['total'] <=> $a['total']);

        return $result;
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<int, array<string, mixed>>
     */
    private function buildByOperator(array $sidingIds, Request $request): array
    {
        // Snapshots are rake-level (imported without wagon numbers), so penalties
        // are split across the rake's loaded wagons proportionally per operator.
        // Preventability is not tracked on rr_penalty_snapshots — reported as 0.
        $rakeTotals = $this->rakePenaltyTotals($sidingIds, $request);
        if ($rakeTotals === []) {
            return [];
        }

        $shares = DB::table('wagon_loading as wl')
            ->join('wagons', 'wagons.id', '=', 'wl.wagon_id')
            ->whereIn('wagons.rake_id', array_keys($rakeTotals))
            ->select(
                'wagons.rake_id',
                DB::raw("COALESCE(NULLIF(wl.loader_operator_name, ''), 'Unknown') as grp"),
                DB::raw('COUNT(*) as wagons'),
            )
            ->groupBy('wagons.rake_id', 'grp')
            ->get();

        $out = $this->distributeRakePenalties($rakeTotals, $shares);
        uasort($out, fn (array $a, array $b) => $b['amount'] <=> $a['amount']);

        return collect($out)->take(15)->map(fn (array $v, string $grp) => [
            'operator' => $grp,
            'penaltyCount' => (int) round($v['count']),
            'totalAmount' => round($v['amount'], 2),
            'preventableCount' => 0,
            'preventablePct' => 0,
        ])->values()->all();
    }

    /**
     * Per-rake penalty totals for the current filter window.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{amount: float, count: int}>
     */
    private function rakePenaltyTotals(array $sidingIds, Request $request): array
    {
        return $this->penaltySnapshotQuery($sidingIds, $request)
            ->select(
                'rr_penalty_snapshots.rake_id',
                DB::raw('SUM(rr_penalty_snapshots.amount) as total'),
                DB::raw('COUNT(*) as cnt'),
            )
            ->groupBy('rr_penalty_snapshots.rake_id')
            ->get()
            ->mapWithKeys(fn ($r) => [(int) $r->rake_id => ['amount' => (float) $r->total, 'count' => (int) $r->cnt]])
            ->all();
    }

    /**
     * Split each rake's penalty amount/count across its groups by wagon share.
     *
     * @param  array<int, array{amount: float, count: int}>  $rakeTotals
     * @param  \Illuminate\Support\Collection<int, object{rake_id: int|string, grp: string, wagons: int|string}>  $shares
     * @return array<string, array{amount: float, count: float}>
     */
    private function distributeRakePenalties(array $rakeTotals, \Illuminate\Support\Collection $shares): array
    {
        $rakeWagonTotals = [];
        foreach ($shares as $row) {
            $rakeWagonTotals[(int) $row->rake_id] = ($rakeWagonTotals[(int) $row->rake_id] ?? 0) + (int) $row->wagons;
        }

        $out = [];
        foreach ($shares as $row) {
            $rakeId = (int) $row->rake_id;
            $totals = $rakeTotals[$rakeId] ?? null;
            $rakeWagons = $rakeWagonTotals[$rakeId] ?? 0;
            if ($totals === null || $rakeWagons === 0) {
                continue;
            }
            $share = (int) $row->wagons / $rakeWagons;
            $out[$row->grp] ??= ['amount' => 0.0, 'count' => 0.0];
            $out[$row->grp]['amount'] += $totals['amount'] * $share;
            $out[$row->grp]['count'] += $totals['count'] * $share;
        }

        return $out;
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<int, array<string, mixed>>
     */
    private function buildByShift(array $sidingIds, Request $request): array
    {
        // Rake-level snapshots split across the rake's loaded wagons by loading shift.
        $rakeTotals = $this->rakePenaltyTotals($sidingIds, $request);
        if ($rakeTotals === []) {
            return [];
        }

        $shares = DB::table('wagon_loading as wl')
            ->join('wagons', 'wagons.id', '=', 'wl.wagon_id')
            ->whereIn('wagons.rake_id', array_keys($rakeTotals))
            ->whereNotNull('wl.loading_time')
            ->select(
                'wagons.rake_id',
                DB::raw("CASE
                WHEN EXTRACT(HOUR FROM wl.loading_time) >= 6 AND EXTRACT(HOUR FROM wl.loading_time) < 14 THEN '1st Shift'
                WHEN EXTRACT(HOUR FROM wl.loading_time) >= 14 AND EXTRACT(HOUR FROM wl.loading_time) < 22 THEN '2nd Shift'
                ELSE '3rd Shift'
            END as grp"),
                DB::raw('COUNT(*) as wagons'),
            )
            ->groupBy('wagons.rake_id', 'grp')
            ->get();

        $out = $this->distributeRakePenalties($rakeTotals, $shares);
        $shiftOrder = ['1st Shift' => 0, '2nd Shift' => 1, '3rd Shift' => 2];

        return collect($out)
            ->map(fn (array $v, string $grp) => [
                'shift' => $grp,
                'penaltyCount' => (int) round($v['count']),
                'totalAmount' => round($v['amount'], 2),
                'preventableCount' => 0,
            ])
            ->sortBy(fn (array $r) => $shiftOrder[$r['shift']] ?? 99)
            ->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<int, array<string, mixed>>
     */
    private function buildByWagonType(array $sidingIds, Request $request): array
    {
        // Rake-level snapshots split across the rake's wagons by wagon type.
        $rakeTotals = $this->rakePenaltyTotals($sidingIds, $request);
        if ($rakeTotals === []) {
            return [];
        }

        $shares = DB::table('wagons')
            ->whereIn('wagons.rake_id', array_keys($rakeTotals))
            ->select(
                'wagons.rake_id',
                DB::raw("COALESCE(NULLIF(wagons.wagon_type, ''), 'Unknown') as grp"),
                DB::raw('COUNT(*) as wagons'),
            )
            ->groupBy('wagons.rake_id', 'grp')
            ->get();

        $out = $this->distributeRakePenalties($rakeTotals, $shares);
        uasort($out, fn (array $a, array $b) => $b['amount'] <=> $a['amount']);

        return collect($out)->take(10)->map(fn (array $v, string $grp) => [
            'wagonType' => $grp,
            'penaltyCount' => (int) round($v['count']),
            'totalAmount' => round($v['amount'], 2),
        ])->values()->all();
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<int, array<string, mixed>>
     */
    private function buildOperatorLeaderboard(array $sidingIds): array
    {
        $rows = DB::table('wagon_loading as wl')
            ->join('wagons', 'wagons.id', '=', 'wl.wagon_id')
            ->join('rakes', 'rakes.id', '=', 'wl.rake_id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->whereNotNull('wl.loader_operator_name')
            ->where('wl.loader_operator_name', '!=', '')
            ->select(
                'wl.loader_operator_name as operator',
                DB::raw('COUNT(*) as total_wagons'),
                DB::raw('SUM(CASE WHEN wl.loaded_quantity_mt > wagons.pcc_weight_mt AND wagons.pcc_weight_mt > 0 THEN 1 ELSE 0 END) as overloaded_wagons'),
                DB::raw('SUM(GREATEST(wl.loaded_quantity_mt - wagons.pcc_weight_mt, 0)) as total_excess_mt'),
            )
            ->groupBy('wl.loader_operator_name')
            ->having(DB::raw('COUNT(*)'), '>=', 5)
            ->orderBy(DB::raw('SUM(CASE WHEN wl.loaded_quantity_mt > wagons.pcc_weight_mt AND wagons.pcc_weight_mt > 0 THEN 1 ELSE 0 END) * 1.0 / COUNT(*)'))
            ->limit(20)
            ->get();

        return $rows->map(fn ($r) => [
            'operator' => $r->operator,
            'totalWagons' => (int) $r->total_wagons,
            'overloadedWagons' => (int) $r->overloaded_wagons,
            'overloadRatePct' => $r->total_wagons > 0
                ? round(($r->overloaded_wagons / $r->total_wagons) * 100, 1)
                : 0,
            'totalExcessMt' => round((float) $r->total_excess_mt, 2),
        ])->values()->all();
    }
}

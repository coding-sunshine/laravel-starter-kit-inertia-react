<?php

declare(strict_types=1);

namespace App\Http\Controllers\RailwayReceipts;

use App\Actions\GeneratePenaltyInsightsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePenaltyRequest;
use App\Models\Penalty;
use App\Models\Siding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class PenaltyController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = $this->sidingIdsForUser($request);

        $query = Penalty::query()
            ->with('rake.siding:id,name,code')
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->latest('penalty_date');

        if ($request->filled('siding_id')) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $request->input('siding_id')));
        }
        if ($request->filled('rake_id')) {
            $query->where('rake_id', $request->input('rake_id'));
        }
        if ($request->filled('status')) {
            $query->where('penalty_status', $request->input('status'));
        }
        if ($request->filled('type')) {
            $query->where('penalty_type', $request->input('type'));
        }
        if ($request->filled('responsible_party')) {
            $query->where('responsible_party', $request->input('responsible_party'));
        }
        if ($request->filled('from')) {
            $query->where('penalty_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->where('penalty_date', '<=', $request->input('to'));
        }

        $penalties = $query->paginate(15)->withQueryString();
        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('penalties/index', [
            'penalties' => $penalties,
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

        $summaryCards = $this->buildAnalyticsSummary($sidingIds);
        $byResponsibleParty = $this->buildByResponsibleParty($sidingIds);
        $byType = $this->buildByType($sidingIds);
        $bySiding = $this->buildBySiding($sidingIds);
        $monthlyTrend = $this->buildMonthlyTrend($sidingIds);
        $topOffenders = $this->buildTopOffenders($sidingIds);
        $weekdayHeatmap = $this->buildWeekdayHeatmap($sidingIds);

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
            'sidings' => $sidings,
            'aiInsights' => Inertia::defer(fn () => resolve(GeneratePenaltyInsightsAction::class)->handle($sidingIds)),
        ]);
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
     * @param  array<int>  $sidingIds
     * @return array<string, mixed>
     */
    private function buildAnalyticsSummary(array $sidingIds): array
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

        $baseQuery = fn () => Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12));

        $total = $baseQuery()->count();
        $totalAmount = (float) $baseQuery()->sum('penalty_amount');

        $byStatus = $baseQuery()
            ->selectRaw('penalty_status, count(*) as count, sum(penalty_amount) as total')
            ->groupBy('penalty_status')
            ->get()
            ->map(fn ($r): array => [
                'status' => $r->penalty_status,
                'count' => (int) $r->count,
                'total' => (float) $r->total,
            ])
            ->values()
            ->all();

        $disputedCount = $baseQuery()->where('penalty_status', 'disputed')->count()
            + $baseQuery()->whereNotNull('disputed_at')->count();
        $waivedCount = $baseQuery()->where('penalty_status', 'waived')->count();
        $disputeSuccessRate = $disputedCount > 0
            ? round(($waivedCount / max(1, $disputedCount + $waivedCount)) * 100, 1)
            : 0;

        return [
            'total_penalties' => $total,
            'total_amount' => round($totalAmount, 2),
            'by_status' => $byStatus,
            'disputed_count' => $disputedCount,
            'waived_count' => $waivedCount,
            'dispute_success_rate' => $disputeSuccessRate,
            'avg_penalty' => $total > 0 ? round($totalAmount / $total, 2) : 0,
        ];
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, value: float, count: int}>
     */
    private function buildByResponsibleParty(array $sidingIds): array
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
     * @param  array<int>  $sidingIds
     * @return array<int, array{name: string, value: float, count: int}>
     */
    private function buildByType(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        return Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12))
            ->selectRaw('penalty_type as name, sum(penalty_amount) as value, count(*) as count')
            ->groupBy('penalty_type')
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
    private function buildBySiding(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $rows = Penalty::query()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->where('penalty_date', '>=', now()->subMonths(12))
            ->selectRaw('sidings.name as siding_name, penalties.penalty_type, sum(penalty_amount) as total, count(*) as count')
            ->groupBy('sidings.name', 'penalties.penalty_type')
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
            $grouped[$name]['types'][$row->penalty_type] = (float) $row->total;
        }

        usort($grouped, fn ($a, $b) => $b['total'] <=> $a['total']);

        return array_values($grouped);
    }

    /**
     * Monthly trend for last 12 months.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{month: string, total: float, count: int}>
     */
    private function buildMonthlyTrend(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $driver = DB::getDriverName();
        $yearMonthSql = $driver === 'pgsql'
            ? 'EXTRACT(YEAR FROM penalty_date)::int as y, EXTRACT(MONTH FROM penalty_date)::int as m'
            : 'YEAR(penalty_date) as y, MONTH(penalty_date) as m';

        return Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12))
            ->selectRaw("{$yearMonthSql}, sum(penalty_amount) as total, count(*) as count")
            ->groupBy('y', 'm')
            ->orderBy('y')
            ->orderBy('m')
            ->get()
            ->map(fn ($r): array => [
                'month' => sprintf('%04d-%02d', (int) $r->y, (int) $r->m),
                'total' => (float) $r->total,
                'count' => (int) $r->count,
            ])
            ->values()
            ->all();
    }

    /**
     * Top 10 rakes/sidings with highest cumulative penalties.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{rake_number: string, siding_name: string, total: float, count: int, types: string}>
     */
    private function buildTopOffenders(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        return Penalty::query()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->where('penalty_date', '>=', now()->subMonths(12))
            ->selectRaw('rakes.rake_number, sidings.name as siding_name, sum(penalty_amount) as total, count(*) as count, GROUP_CONCAT(DISTINCT penalties.penalty_type) as types')
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
    private function buildWeekdayHeatmap(array $sidingIds): array
    {
        if ($sidingIds === []) {
            return [];
        }

        $driver = DB::getDriverName();
        $dowSql = $driver === 'pgsql'
            ? 'EXTRACT(DOW FROM penalty_date)::int as day'
            : 'DAYOFWEEK(penalty_date) - 1 as day';

        return Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(12))
            ->selectRaw("{$dowSql}, count(*) as count, sum(penalty_amount) as total")
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
}

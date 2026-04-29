<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataTables\PenaltyDataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\QueryBuilder;

final readonly class BuildPenaltyChartDataAction
{
    /**
     * Build chart aggregates from the same filtered query as the penalties DataTable.
     * When no date filter is applied, constrains to last 12 months for consistency with analytics.
     *
     * @return array{byType: array<int, array{name: string, value: float, count: int}>, bySiding: array<int, array{name: string, total: float}>, monthlyTrend: array<int, array{month: string, total: float, count: int}>}
     */
    public function handle(Request $request): array
    {
        $hasDateFilter = $this->hasDateFilter($request);

        return [
            'byType' => $this->buildByType($this->filteredQuery($request, $hasDateFilter)),
            'bySiding' => $this->buildBySiding($this->filteredQuery($request, $hasDateFilter)),
            'monthlyTrend' => $this->buildMonthlyTrend($this->filteredQuery($request, $hasDateFilter), $hasDateFilter),
        ];
    }

    private function hasDateFilter(Request $request): bool
    {
        $filters = $request->get('filter', []);

        return isset($filters['penalty_date']);
    }

    /**
     * @return QueryBuilder<\App\Models\Penalty>
     */
    private function filteredQuery(Request $request, bool $hasDateFilter): QueryBuilder
    {
        $query = QueryBuilder::for(PenaltyDataTable::tableBaseQuery())
            ->allowedFilters(...PenaltyDataTable::tableAllowedFilters());

        if (! $hasDateFilter) {
            $query->where('penalty_date', '>=', now()->subMonths(12));
        }

        return $query;
    }

    /**
     * @param  QueryBuilder<\App\Models\Penalty>  $query
     * @return array<int, array{name: string, value: float, count: int}>
     */
    private function buildByType(QueryBuilder $query): array
    {
        $rows = $query
            ->getQuery()
            ->selectRaw('penalty_type as name, sum(penalty_amount) as value, count(*) as count')
            ->groupBy('penalty_type')
            ->orderByDesc('value')
            ->get();

        return $rows->map(fn ($r): array => [
            'name' => (string) $r->name,
            'value' => (float) $r->value,
            'count' => (int) $r->count,
        ])->values()->all();
    }

    /**
     * @param  QueryBuilder<\App\Models\Penalty>  $query
     * @return array<int, array{name: string, total: float}>
     */
    private function buildBySiding(QueryBuilder $query): array
    {
        $rows = $query
            ->getQuery()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->selectRaw('sidings.name as name, sum(penalty_amount) as total')
            ->groupBy('sidings.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return $rows->map(fn ($r): array => [
            'name' => (string) $r->name,
            'total' => (float) $r->total,
        ])->values()->all();
    }

    /**
     * @param  QueryBuilder<\App\Models\Penalty>  $query
     * @return array<int, array{month: string, total: float, count: int}>
     */
    private function buildMonthlyTrend(QueryBuilder $query, bool $hasDateFilter): array
    {
        $driver = DB::getDriverName();
        $yearMonthSql = match ($driver) {
            'pgsql' => 'EXTRACT(YEAR FROM penalty_date)::int as y, EXTRACT(MONTH FROM penalty_date)::int as m',
            'sqlite' => "CAST(strftime('%Y', penalty_date) AS INTEGER) as y, CAST(strftime('%m', penalty_date) AS INTEGER) as m",
            default => 'YEAR(penalty_date) as y, MONTH(penalty_date) as m',
        };

        $rows = $query
            ->getQuery()
            ->selectRaw("{$yearMonthSql}, sum(penalty_amount) as total, count(*) as count")
            ->groupBy('y', 'm')
            ->orderBy('y')
            ->orderBy('m')
            ->get();

        if ($hasDateFilter) {
            return $rows->map(fn ($r): array => [
                'month' => \Carbon\Carbon::createFromDate((int) $r->y, (int) $r->m, 1)->format('M Y'),
                'total' => (float) $r->total,
                'count' => (int) $r->count,
            ])->values()->all();
        }

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

        foreach ($rows as $r) {
            $key = sprintf('%04d-%02d', (int) $r->y, (int) $r->m);
            if (isset($months[$key])) {
                $months[$key]['total'] = (float) $r->total;
                $months[$key]['count'] = (int) $r->count;
            }
        }

        return array_values($months);
    }
}

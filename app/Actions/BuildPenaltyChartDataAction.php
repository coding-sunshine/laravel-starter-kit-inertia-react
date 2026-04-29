<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final readonly class BuildPenaltyChartDataAction
{
    /**
     * @return array{byType: array<int, array{name: string, value: float, count: int}>, bySiding: array<int, array{name: string, total: float}>, monthlyTrend: array<int, array{month: string, total: float, count: int}>}
     */
    public function handle(Request $request): array
    {
        $hasDateFilter = $this->hasDateFilter($request);

        return [
            'byType' => $this->buildByType($hasDateFilter, $request),
            'bySiding' => $this->buildBySiding($hasDateFilter, $request),
            'monthlyTrend' => $this->buildMonthlyTrend($hasDateFilter, $request),
        ];
    }

    private function hasDateFilter(Request $request): bool
    {
        $filters = $request->get('filter', []);

        return isset($filters['created_at']);
    }

    private function baseQuery(bool $hasDateFilter, Request $request): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('applied_penalties')
            ->join('penalty_types', 'applied_penalties.penalty_type_id', '=', 'penalty_types.id')
            ->join('rakes', 'applied_penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id');

        if (! $hasDateFilter) {
            $query->where('applied_penalties.created_at', '>=', now()->startOfMonth()->subMonthsNoOverflow(11));
        } else {
            $filters = $request->get('filter', []);
            if (isset($filters['created_at'])) {
                $query->whereDate('applied_penalties.created_at', $filters['created_at']);
            }
        }

        return $query;
    }

    /**
     * @return array<int, array{name: string, value: float, count: int}>
     */
    private function buildByType(bool $hasDateFilter, Request $request): array
    {
        $rows = $this->baseQuery($hasDateFilter, $request)
            ->selectRaw('penalty_types.code as name, sum(applied_penalties.amount) as value, count(*) as count')
            ->groupBy('penalty_types.code')
            ->orderByDesc('value')
            ->get();

        return $rows->map(fn ($r): array => [
            'name' => (string) $r->name,
            'value' => (float) $r->value,
            'count' => (int) $r->count,
        ])->values()->all();
    }

    /**
     * @return array<int, array{name: string, total: float}>
     */
    private function buildBySiding(bool $hasDateFilter, Request $request): array
    {
        $rows = $this->baseQuery($hasDateFilter, $request)
            ->selectRaw('sidings.name as name, sum(applied_penalties.amount) as total')
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
     * @return array<int, array{month: string, total: float, count: int}>
     */
    private function buildMonthlyTrend(bool $hasDateFilter, Request $request): array
    {
        $driver = DB::getDriverName();
        $yearMonthSql = match ($driver) {
            'pgsql' => 'EXTRACT(YEAR FROM applied_penalties.created_at)::int as y, EXTRACT(MONTH FROM applied_penalties.created_at)::int as m',
            'sqlite' => "CAST(strftime('%Y', applied_penalties.created_at) AS INTEGER) as y, CAST(strftime('%m', applied_penalties.created_at) AS INTEGER) as m",
            default => 'YEAR(applied_penalties.created_at) as y, MONTH(applied_penalties.created_at) as m',
        };

        $rows = $this->baseQuery($hasDateFilter, $request)
            ->selectRaw("{$yearMonthSql}, sum(applied_penalties.amount) as total, count(*) as count")
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

        $now = now()->startOfMonth();
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = $now->copy()->subMonthsNoOverflow($i);
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

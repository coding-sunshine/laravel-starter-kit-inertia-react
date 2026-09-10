<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\PenaltyDateFilter;
use Illuminate\Database\Query\Builder;
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

        return isset($filters['penalty_date']);
    }

    private function baseQuery(bool $hasDateFilter, Request $request): Builder
    {
        $query = DB::table('rr_penalty_snapshots')
            ->leftJoin('rr_documents', 'rr_penalty_snapshots.rr_document_id', '=', 'rr_documents.id')
            ->join('rakes', 'rr_penalty_snapshots.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->leftJoin('penalty_types', 'rr_penalty_snapshots.penalty_code', '=', 'penalty_types.code');

        if (! $hasDateFilter) {
            $query->whereRaw(PenaltyDateFilter::DATE_EXPR.' >= ?', [now()->startOfMonth()->subMonthsNoOverflow(11)]);
        } else {
            $filters = $request->get('filter', []);
            PenaltyDateFilter::apply($query, $filters['penalty_date']);
        }

        return $query;
    }

    /**
     * @return array<int, array{name: string, value: float, count: int}>
     */
    private function buildByType(bool $hasDateFilter, Request $request): array
    {
        $rows = $this->baseQuery($hasDateFilter, $request)
            ->selectRaw('rr_penalty_snapshots.penalty_code as name, sum(rr_penalty_snapshots.amount) as value, count(*) as count')
            ->groupBy('rr_penalty_snapshots.penalty_code')
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
            ->selectRaw('sidings.name as name, sum(rr_penalty_snapshots.amount) as total')
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
        $dateExpr = PenaltyDateFilter::DATE_EXPR;
        $yearMonthSql = match ($driver) {
            'pgsql' => "EXTRACT(YEAR FROM ({$dateExpr}))::int as y, EXTRACT(MONTH FROM ({$dateExpr}))::int as m",
            'sqlite' => "CAST(strftime('%Y', {$dateExpr}) AS INTEGER) as y, CAST(strftime('%m', {$dateExpr}) AS INTEGER) as m",
            default => "YEAR({$dateExpr}) as y, MONTH({$dateExpr}) as m",
        };

        $rows = $this->baseQuery($hasDateFilter, $request)
            ->selectRaw("{$yearMonthSql}, sum(rr_penalty_snapshots.amount) as total, count(*) as count")
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

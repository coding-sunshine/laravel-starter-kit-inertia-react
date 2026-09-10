<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\PenaltyType;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class PenaltySummaryTool implements Tool
{
    /**
     * @param  array<int>  $sidingIds
     */
    public function __construct(private readonly array $sidingIds) {}

    public function description(): Stringable|string
    {
        return 'Query RR penalty snapshot data with filters for date range, penalty type, and siding. Returns counts, totals, and breakdowns. Use this when users ask about penalties, penalty amounts, penalty trends, or penalty statistics.';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = DB::table('rr_penalty_snapshots as rps')
            ->join('penalty_types as pt', 'pt.code', '=', 'rps.penalty_code')
            ->leftJoin('rakes as r', 'r.id', '=', 'rps.rake_id')
            ->leftJoin('rr_documents as rd', 'rd.id', '=', 'rps.rr_document_id')
            ->whereIn('r.siding_id', $this->sidingIds);

        if ($request['date_from'] ?? null) {
            $query->whereDate('rd.rr_received_date', '>=', $request['date_from']);
        }

        if ($request['date_to'] ?? null) {
            $query->whereDate('rd.rr_received_date', '<=', $request['date_to']);
        }

        if ($request['penalty_type'] ?? null) {
            $query->where('rps.penalty_code', $request['penalty_type']);
        }

        $groupBy = $request['group_by'] ?? 'total';

        if ($groupBy === 'siding') {
            $results = (clone $query)
                ->join('sidings as s', 's.id', '=', 'r.siding_id')
                ->select('s.name as group_key', DB::raw('count(*) as count'), DB::raw('sum(rps.amount) as total_amount'))
                ->groupBy('s.name')
                ->orderByDesc('total_amount')
                ->limit(20)
                ->get();
        } elseif ($groupBy === 'type') {
            $results = (clone $query)
                ->select('rps.penalty_code as group_key', DB::raw('count(*) as count'), DB::raw('sum(rps.amount) as total_amount'))
                ->groupBy('rps.penalty_code')
                ->orderByDesc('total_amount')
                ->limit(20)
                ->get();
        } elseif ($groupBy === 'month') {
            $monthSql = DB::getDriverName() === 'pgsql'
                ? "to_char(rd.rr_received_date, 'YYYY-MM')"
                : "DATE_FORMAT(rd.rr_received_date, '%Y-%m')";

            $results = (clone $query)
                ->select(DB::raw("{$monthSql} as group_key"), DB::raw('count(*) as count'), DB::raw('sum(rps.amount) as total_amount'))
                ->groupBy('group_key')
                ->orderBy('group_key')
                ->limit(12)
                ->get();
        } else {
            $count = (clone $query)->count();
            $total = (float) (clone $query)->sum('rps.amount');
            $avg = $count > 0 ? round($total / $count, 2) : 0;

            return json_encode([
                'total_penalties' => $count,
                'total_amount' => $total,
                'average_amount' => $avg,
            ], JSON_THROW_ON_ERROR);
        }

        return json_encode([
            'grouped_by' => $groupBy,
            'results' => $results->map(fn ($r): array => [
                'group' => $r->group_key,
                'count' => (int) $r->count,
                'total_amount' => (float) $r->total_amount,
            ])->all(),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $codes = PenaltyType::query()->pluck('code')->implode(', ');

        return [
            'date_from' => $schema->string()->description('Start date filter (YYYY-MM-DD). Defaults to all time if omitted.'),
            'date_to' => $schema->string()->description('End date filter (YYYY-MM-DD). Defaults to today if omitted.'),
            'penalty_type' => $schema->string()->description("Filter by penalty type code (e.g. {$codes})."),
            'group_by' => $schema->string()->enum(['total', 'siding', 'type', 'month'])->description('How to group results. Use "total" for a single summary. Defaults to "total".'),
        ];
    }
}

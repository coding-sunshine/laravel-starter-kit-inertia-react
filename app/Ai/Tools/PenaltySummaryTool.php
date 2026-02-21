<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Penalty;
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
        return 'Query penalty data with filters for date range, penalty type, siding, status, and responsible party. Returns counts, totals, and breakdowns. Use this when users ask about penalties, penalty amounts, penalty trends, or penalty statistics.';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $this->sidingIds));

        if ($request['date_from'] ?? null) {
            $query->where('penalty_date', '>=', $request['date_from']);
        }

        if ($request['date_to'] ?? null) {
            $query->where('penalty_date', '<=', $request['date_to']);
        }

        if ($request['penalty_type'] ?? null) {
            $query->where('penalty_type', $request['penalty_type']);
        }

        if ($request['status'] ?? null) {
            $query->where('penalty_status', $request['status']);
        }

        if ($request['responsible_party'] ?? null) {
            $query->where('responsible_party', $request['responsible_party']);
        }

        $groupBy = $request['group_by'] ?? 'total';

        if ($groupBy === 'siding') {
            $results = (clone $query)
                ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
                ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
                ->select('sidings.name as group_key', DB::raw('count(*) as count'), DB::raw('sum(penalty_amount) as total_amount'))
                ->groupBy('sidings.name')
                ->orderByDesc('total_amount')
                ->limit(20)
                ->get();
        } elseif ($groupBy === 'type') {
            $results = (clone $query)
                ->select('penalty_type as group_key', DB::raw('count(*) as count'), DB::raw('sum(penalty_amount) as total_amount'))
                ->groupBy('penalty_type')
                ->orderByDesc('total_amount')
                ->limit(20)
                ->get();
        } elseif ($groupBy === 'status') {
            $results = (clone $query)
                ->select('penalty_status as group_key', DB::raw('count(*) as count'), DB::raw('sum(penalty_amount) as total_amount'))
                ->groupBy('penalty_status')
                ->orderByDesc('total_amount')
                ->get();
        } elseif ($groupBy === 'month') {
            $results = (clone $query)
                ->select(DB::raw("to_char(penalty_date, 'YYYY-MM') as group_key"), DB::raw('count(*) as count'), DB::raw('sum(penalty_amount) as total_amount'))
                ->groupBy('group_key')
                ->orderBy('group_key')
                ->limit(12)
                ->get();
        } elseif ($groupBy === 'responsible_party') {
            $results = (clone $query)
                ->whereNotNull('responsible_party')
                ->select('responsible_party as group_key', DB::raw('count(*) as count'), DB::raw('sum(penalty_amount) as total_amount'))
                ->groupBy('responsible_party')
                ->orderByDesc('total_amount')
                ->limit(20)
                ->get();
        } else {
            $count = (clone $query)->count();
            $total = (float) (clone $query)->sum('penalty_amount');
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
        return [
            'date_from' => $schema->string()->description('Start date filter (YYYY-MM-DD). Defaults to all time if omitted.'),
            'date_to' => $schema->string()->description('End date filter (YYYY-MM-DD). Defaults to today if omitted.'),
            'penalty_type' => $schema->string()->description('Filter by penalty type code (e.g. DEM, POL1, POLA, PLO, ULC, SPL, WMC, MCF).'),
            'status' => $schema->string()->enum(['pending', 'incurred', 'waived', 'disputed'])->description('Filter by penalty status.'),
            'responsible_party' => $schema->string()->description('Filter by responsible party name.'),
            'group_by' => $schema->string()->enum(['total', 'siding', 'type', 'status', 'month', 'responsible_party'])->description('How to group results. Use "total" for a single summary. Defaults to "total".'),
        ];
    }
}

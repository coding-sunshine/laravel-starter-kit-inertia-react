<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\SidingPerformance;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class SidingPerformanceTool implements Tool
{
    /**
     * @param  array<int>  $sidingIds
     */
    public function __construct(private readonly array $sidingIds) {}

    public function description(): Stringable|string
    {
        return 'Query siding performance metrics including rakes processed, penalty amounts, demurrage hours, and overload incidents. Use this when users ask about siding performance, efficiency, or comparisons between sidings.';
    }

    public function handle(Request $request): Stringable|string
    {
        $days = (int) ($request['days'] ?? 7);
        $days = min(max($days, 1), 90);

        $query = SidingPerformance::query()
            ->join('sidings', 'siding_performance.siding_id', '=', 'sidings.id')
            ->whereIn('siding_performance.siding_id', $this->sidingIds)
            ->where('as_of_date', '>=', now()->subDays($days)->toDateString());

        if ($request['siding_name'] ?? null) {
            $query->where('sidings.name', 'ILIKE', '%'.$request['siding_name'].'%');
        }

        $results = $query
            ->select(
                'sidings.name as siding_name',
                DB::raw('sum(rakes_processed) as total_rakes'),
                DB::raw('sum(total_penalty_amount) as total_penalties'),
                DB::raw('sum(penalty_incidents) as total_incidents'),
                DB::raw('avg(average_demurrage_hours) as avg_demurrage_hours'),
                DB::raw('sum(overload_incidents) as total_overloads'),
                DB::raw('avg(closing_stock_mt) as avg_stock_mt'),
            )
            ->groupBy('sidings.name')
            ->orderByDesc('total_penalties')
            ->toBase()
            ->get();

        return json_encode([
            'period_days' => $days,
            'sidings' => $results->map(fn ($r): array => [
                'siding' => $r->siding_name,
                'rakes_processed' => (int) $r->total_rakes,
                'total_penalty_amount' => round((float) $r->total_penalties, 2),
                'penalty_incidents' => (int) $r->total_incidents,
                'avg_demurrage_hours' => round((float) $r->avg_demurrage_hours, 1),
                'overload_incidents' => (int) $r->total_overloads,
                'avg_closing_stock_mt' => round((float) $r->avg_stock_mt, 1),
            ])->all(),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()->min(1)->description('Number of days to look back (1-90). Defaults to 7.'),
            'siding_name' => $schema->string()->description('Filter by siding name (partial match).'),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Penalty;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class DisputeAnalysisTool implements Tool
{
    /**
     * @param  array<int>  $sidingIds
     */
    public function __construct(private readonly array $sidingIds) {}

    public function description(): Stringable|string
    {
        return 'Analyze dispute outcomes and success rates. Shows disputed vs waived penalties, success rates by type and party, and undisputed penalties that could be challenged. Use this when users ask about disputes, dispute success rates, or whether they should dispute penalties.';
    }

    public function handle(Request $request): Stringable|string
    {
        $days = (int) ($request['days'] ?? 90);
        $days = min(max($days, 1), 365);

        $baseQuery = fn () => Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $this->sidingIds))
            ->where('penalty_date', '>=', now()->subDays($days));

        $disputed = $baseQuery()->where('penalty_status', 'disputed')->count();
        $waived = $baseQuery()->where('penalty_status', 'waived')->count();
        $total = $disputed + $waived;
        $successRate = $total > 0 ? round($waived / $total * 100, 1) : 0;

        $waivedAmount = (float) $baseQuery()->where('penalty_status', 'waived')->sum('penalty_amount');

        // By penalty type
        $byType = $baseQuery()
            ->whereIn('penalty_status', ['disputed', 'waived'])
            ->select(
                'penalty_type',
                DB::raw("count(case when penalty_status = 'waived' then 1 end) as waived_count"),
                DB::raw("count(case when penalty_status = 'disputed' then 1 end) as disputed_count"),
                DB::raw("sum(case when penalty_status = 'waived' then penalty_amount else 0 end) as waived_amount"),
            )
            ->groupBy('penalty_type')
            ->orderByDesc('waived_amount')
            ->get()
            ->map(fn ($r): array => [
                'type' => $r->penalty_type,
                'waived' => (int) $r->waived_count,
                'disputed' => (int) $r->disputed_count,
                'total' => (int) $r->waived_count + (int) $r->disputed_count,
                'success_rate' => ((int) $r->waived_count + (int) $r->disputed_count) > 0
                    ? round((int) $r->waived_count / ((int) $r->waived_count + (int) $r->disputed_count) * 100, 1)
                    : 0,
                'amount_saved' => (float) $r->waived_amount,
            ])->all();

        // Undisputed penalties
        $undisputed = $baseQuery()
            ->whereIn('penalty_status', ['incurred', 'pending'])
            ->whereNull('disputed_at')
            ->selectRaw('count(*) as count, sum(penalty_amount) as total')
            ->first();

        return json_encode([
            'period_days' => $days,
            'overall' => [
                'total_challenged' => $total,
                'waived' => $waived,
                'still_disputed' => $disputed,
                'success_rate_pct' => $successRate,
                'total_saved' => $waivedAmount,
            ],
            'by_type' => $byType,
            'undisputed' => [
                'count' => (int) ($undisputed->count ?? 0),
                'total_amount' => (float) ($undisputed->total ?? 0),
            ],
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'days' => $schema->integer()->min(1)->description('Number of days to look back (1-365). Defaults to 90.'),
        ];
    }
}

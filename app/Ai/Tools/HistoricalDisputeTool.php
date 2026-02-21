<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Penalty;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class HistoricalDisputeTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Query historical dispute outcomes for similar penalties. Searches past disputed/waived penalties by type, responsible party, and amount range to estimate dispute success probability.';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = Penalty::query()
            ->whereIn('penalty_status', ['disputed', 'waived'])
            ->whereNotNull('disputed_at');

        if ($request['penalty_type'] ?? null) {
            $query->where('penalty_type', $request['penalty_type']);
        }

        if ($request['responsible_party'] ?? null) {
            $query->where('responsible_party', $request['responsible_party']);
        }

        if ($request['min_amount'] ?? null) {
            $query->where('penalty_amount', '>=', $request['min_amount']);
        }

        if ($request['max_amount'] ?? null) {
            $query->where('penalty_amount', '<=', $request['max_amount']);
        }

        $total = (clone $query)->count();
        $waived = (clone $query)->where('penalty_status', 'waived')->count();
        $successRate = $total > 0 ? round($waived / $total * 100, 1) : 0;

        $waivedAmount = (float) (clone $query)->where('penalty_status', 'waived')->sum('penalty_amount');

        // Common dispute reasons for successful ones
        $successfulReasons = Penalty::query()
            ->where('penalty_status', 'waived')
            ->whereNotNull('dispute_reason')
            ->where('dispute_reason', '!=', '');

        if ($request['penalty_type'] ?? null) {
            $successfulReasons->where('penalty_type', $request['penalty_type']);
        }

        $reasons = $successfulReasons
            ->selectRaw('dispute_reason, count(*) as count')
            ->groupBy('dispute_reason')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'dispute_reason')
            ->all();

        // Average resolution time
        $driver = DB::getDriverName();
        $diffSql = $driver === 'pgsql'
            ? 'AVG(EXTRACT(EPOCH FROM (resolved_at - disputed_at)) / 86400)'
            : 'AVG(DATEDIFF(resolved_at, disputed_at))';

        $avgDays = (float) Penalty::query()
            ->where('penalty_status', 'waived')
            ->whereNotNull('disputed_at')
            ->whereNotNull('resolved_at')
            ->when($request['penalty_type'] ?? null, fn ($q) => $q->where('penalty_type', $request['penalty_type']))
            ->selectRaw("{$diffSql} as avg_days")
            ->value('avg_days');

        return json_encode([
            'similar_disputes' => $total,
            'waived' => $waived,
            'success_rate_pct' => $successRate,
            'total_amount_saved' => $waivedAmount,
            'avg_resolution_days' => round($avgDays, 1),
            'successful_dispute_reasons' => $reasons,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'penalty_type' => $schema->string()->description('Filter by penalty type code (e.g. DEM, POL1).'),
            'responsible_party' => $schema->string()->description('Filter by responsible party.'),
            'min_amount' => $schema->number()->description('Minimum penalty amount to match.'),
            'max_amount' => $schema->number()->description('Maximum penalty amount to match.'),
        ];
    }
}

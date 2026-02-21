<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\Rake;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class RakeStatusTool implements Tool
{
    /**
     * @param  array<int>  $sidingIds
     */
    public function __construct(private readonly array $sidingIds) {}

    public function description(): Stringable|string
    {
        return 'Query current rake states, loading times, and demurrage risk. Use this when users ask about rakes, loading progress, remaining free time, or demurrage risk.';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = Rake::query()
            ->with('siding:id,name')
            ->whereIn('siding_id', $this->sidingIds);

        if ($request['state'] ?? null) {
            $query->where('state', $request['state']);
        }

        if ($request['rake_number'] ?? null) {
            $query->where('rake_number', 'ILIKE', '%'.$request['rake_number'].'%');
        }

        $mode = $request['mode'] ?? 'summary';

        if ($mode === 'demurrage_risk') {
            $rakes = (clone $query)
                ->where('state', 'loading')
                ->whereNotNull('loading_start_time')
                ->whereNotNull('free_time_minutes')
                ->get();

            $atRisk = [];
            foreach ($rakes as $rake) {
                $end = $rake->loading_start_time->copy()->addMinutes((int) $rake->free_time_minutes);
                $remaining = (int) now()->diffInMinutes($end, false);
                $atRisk[] = [
                    'rake_number' => $rake->rake_number,
                    'siding' => $rake->siding?->name,
                    'remaining_minutes' => max(0, $remaining),
                    'exceeded' => $remaining <= 0,
                    'risk_level' => $remaining <= 0 ? 'exceeded' : ($remaining <= 30 ? 'critical' : ($remaining <= 60 ? 'high' : 'normal')),
                ];
            }

            usort($atRisk, fn ($a, $b) => $a['remaining_minutes'] <=> $b['remaining_minutes']);

            return json_encode(['demurrage_risk_rakes' => array_slice($atRisk, 0, 20)], JSON_THROW_ON_ERROR);
        }

        if ($mode === 'list') {
            $rakes = $query->latest('updated_at')->limit(20)->get();

            return json_encode([
                'rakes' => $rakes->map(fn (Rake $r): array => [
                    'rake_number' => $r->rake_number,
                    'siding' => $r->siding?->name,
                    'state' => $r->state,
                    'loading_start' => $r->loading_start_time?->toDateTimeString(),
                    'loading_end' => $r->loading_end_time?->toDateTimeString(),
                    'free_time_minutes' => $r->free_time_minutes,
                    'demurrage_hours' => $r->demurrage_hours,
                    'demurrage_amount' => $r->demurrage_penalty_amount,
                ])->all(),
            ], JSON_THROW_ON_ERROR);
        }

        // Summary mode
        $counts = (clone $query)
            ->select('state', DB::raw('count(*) as count'))
            ->groupBy('state')
            ->pluck('count', 'state')
            ->all();

        $loadingCount = Rake::query()
            ->whereIn('siding_id', $this->sidingIds)
            ->where('state', 'loading')
            ->count();

        return json_encode([
            'state_counts' => $counts,
            'total_rakes' => array_sum($counts),
            'currently_loading' => $loadingCount,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'state' => $schema->string()->enum(['pending', 'loading', 'loaded', 'dispatched'])->description('Filter by rake state.'),
            'rake_number' => $schema->string()->description('Search by rake number (partial match).'),
            'mode' => $schema->string()->enum(['summary', 'list', 'demurrage_risk'])->description('Output mode: "summary" for counts, "list" for detailed rake list, "demurrage_risk" for loading rakes sorted by urgency. Defaults to "summary".'),
        ];
    }
}

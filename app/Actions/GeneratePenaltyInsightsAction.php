<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Penalty;
use App\Services\PrismService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class GeneratePenaltyInsightsAction
{
    public function __construct(private PrismService $prism) {}

    /**
     * Generate AI-powered penalty insights for a set of sidings.
     * Cached for 24 hours; called weekly by scheduled command.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{title: string, description: string, severity: string}>|null
     */
    public function handle(array $sidingIds): ?array
    {
        if ($sidingIds === []) {
            return null;
        }

        $cacheKey = 'penalty_insights:'.implode(',', $sidingIds);

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);

            return $cached === '__unavailable__' ? null : $cached;
        }

        if (! $this->prism->isAvailable()) {
            Cache::put($cacheKey, '__unavailable__', 86400);

            return null;
        }

        $data = $this->aggregateData($sidingIds);
        $prompt = $this->buildPrompt($data);

        try {
            $response = $this->prism->generate($prompt, $this->prism->fastModel());
            $text = mb_trim($response->text);
            $insights = $this->parseInsights($text);
            Cache::put($cacheKey, $insights, 86400);

            return $insights;
        } catch (Throwable) {
            Cache::put($cacheKey, '__unavailable__', 3600);

            return null;
        }
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<string, mixed>
     */
    private function aggregateData(array $sidingIds): array
    {
        $baseQuery = fn () => Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subMonths(3));

        // By type
        $byType = $baseQuery()
            ->selectRaw('penalty_type, count(*) as cnt, sum(penalty_amount) as total')
            ->groupBy('penalty_type')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r): array => [
                'type' => $r->penalty_type,
                'count' => (int) $r->cnt,
                'total' => (float) $r->total,
            ])
            ->all();

        // By responsible party
        $byResponsible = $baseQuery()
            ->whereNotNull('responsible_party')
            ->selectRaw('responsible_party, count(*) as cnt, sum(penalty_amount) as total')
            ->groupBy('responsible_party')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r): array => [
                'party' => $r->responsible_party,
                'count' => (int) $r->cnt,
                'total' => (float) $r->total,
            ])
            ->all();

        // By siding
        $bySiding = $baseQuery()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->selectRaw('sidings.name, count(*) as cnt, sum(penalty_amount) as total')
            ->groupBy('sidings.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($r): array => [
                'siding' => $r->name,
                'count' => (int) $r->cnt,
                'total' => (float) $r->total,
            ])
            ->all();

        // By day of week
        $driver = DB::getDriverName();
        $dowSql = $driver === 'pgsql'
            ? 'EXTRACT(DOW FROM penalty_date)::int'
            : 'DAYOFWEEK(penalty_date) - 1';

        $byDow = $baseQuery()
            ->selectRaw("{$dowSql} as dow, count(*) as cnt")
            ->groupBy('dow')
            ->orderByDesc('cnt')
            ->get()
            ->map(fn ($r): array => [
                'day' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][(int) $r->dow] ?? '?',
                'count' => (int) $r->cnt,
            ])
            ->all();

        // Monthly trend (last 3 months)
        $monthTotals = [];
        for ($i = 2; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $total = (float) Penalty::query()
                ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
                ->whereMonth('penalty_date', $month->month)
                ->whereYear('penalty_date', $month->year)
                ->sum('penalty_amount');
            $monthTotals[] = [
                'month' => $month->format('M Y'),
                'total' => $total,
            ];
        }

        // Dispute outcomes
        $disputed = $baseQuery()->where('penalty_status', 'disputed')->count();
        $waived = $baseQuery()->where('penalty_status', 'waived')->count();

        // By root cause (top 10)
        $like = $driver === 'pgsql' ? 'ILIKE' : 'LIKE';
        $caseSql = "CASE
            WHEN root_cause {$like} '%equipment%' OR root_cause {$like} '%breakdown%' THEN 'Equipment Failure'
            WHEN root_cause {$like} '%labour%' OR root_cause {$like} '%crew%' THEN 'Labour Shortage'
            WHEN root_cause {$like} '%weather%' OR root_cause {$like} '%rain%' OR root_cause {$like} '%fog%' THEN 'Weather/Environmental'
            WHEN root_cause {$like} '%communication%' OR root_cause {$like} '%miscommuni%' THEN 'Communication Gap'
            WHEN root_cause {$like} '%scheduling%' THEN 'Scheduling Error'
            WHEN root_cause {$like} '%documentation%' OR root_cause {$like} '%paperwork%' THEN 'Documentation Delay'
            WHEN root_cause {$like} '%infrastructure%' OR root_cause {$like} '%track%' THEN 'Infrastructure Issue'
            WHEN root_cause {$like} '%operational%' OR root_cause {$like} '%shunting%' THEN 'Operational Delay'
            ELSE 'Other'
        END";

        $byRootCause = $baseQuery()
            ->whereNotNull('root_cause')
            ->where('root_cause', '!=', '')
            ->selectRaw("{$caseSql} as cause, count(*) as cnt, sum(penalty_amount) as total")
            ->groupBy('cause')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r): array => [
                'cause' => (string) $r->cause,
                'count' => (int) $r->cnt,
                'total' => (float) $r->total,
            ])
            ->all();

        // Average resolution days
        $diffSql = $driver === 'pgsql'
            ? 'AVG(EXTRACT(EPOCH FROM (resolved_at - disputed_at)) / 86400)'
            : 'AVG(DATEDIFF(resolved_at, disputed_at))';

        $avgResolutionDays = (float) $baseQuery()
            ->whereNotNull('disputed_at')
            ->whereNotNull('resolved_at')
            ->selectRaw("{$diffSql} as avg_days")
            ->value('avg_days');

        // Undisputed penalties (never challenged)
        $undisputed = $baseQuery()
            ->whereIn('penalty_status', ['incurred', 'pending'])
            ->whereNull('disputed_at')
            ->selectRaw('count(*) as cnt, sum(penalty_amount) as total')
            ->first();

        return [
            'by_type' => $byType,
            'by_responsible' => $byResponsible,
            'by_siding' => $bySiding,
            'by_day_of_week' => $byDow,
            'monthly_trend' => $monthTotals,
            'disputed' => $disputed,
            'waived' => $waived,
            'by_root_cause' => $byRootCause,
            'avg_resolution_days' => round($avgResolutionDays, 1),
            'undisputed_count' => (int) ($undisputed->cnt ?? 0),
            'undisputed_amount' => (float) ($undisputed->total ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildPrompt(array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);

        return <<<PROMPT
        You are RRMCS AI, a cost-reduction analyst for railway coal operations. Analyze these penalty patterns from the last 3 months and provide exactly 5 actionable cost-saving recommendations.

        Penalty data:
        {$json}

        For each recommendation, output it on its own line in this exact format:
        [SEVERITY] Title | Description

        Where SEVERITY is one of: HIGH, MEDIUM, LOW
        Title is a short actionable heading (under 60 chars).
        Description is 1-2 sentences explaining the insight and what to do about it. MUST include a projected savings figure (₹).

        Focus your analysis on these cost-saving opportunities:
        1. ROOT CAUSES: Which root causes are preventable? What is the projected savings from reducing equipment failures, labour issues, or scheduling errors?
        2. DISPUTE STRATEGY: There are {$data['undisputed_count']} undisputed penalties worth ₹{$data['undisputed_amount']}. Should more be contested? What is the projected savings based on current dispute success rate?
        3. RESPONSIBLE PARTY PATTERNS: Which parties cause the most expensive penalties? What accountability measures would reduce costs?
        4. PENALTY TYPE TRENDS: Which types are growing? What specific operational changes would reduce them?
        5. SIDING HOTSPOTS: Which sidings need intervention? What would bringing the worst siding to median performance save?

        Be specific with numbers, percentages, and projected ₹ savings. Every recommendation must include a savings estimate.
        PROMPT;
    }

    /**
     * Parse AI response into structured insights.
     *
     * @return array<int, array{title: string, description: string, severity: string}>
     */
    private function parseInsights(string $text): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        $insights = [];

        foreach ($lines as $line) {
            if (preg_match('/^\[?(HIGH|MEDIUM|LOW)\]?\s*(.+?)\s*\|\s*(.+)$/i', $line, $matches)) {
                $insights[] = [
                    'severity' => mb_strtolower($matches[1]),
                    'title' => mb_trim($matches[2]),
                    'description' => mb_trim($matches[3]),
                ];
            }
        }

        return array_slice($insights, 0, 5);
    }
}

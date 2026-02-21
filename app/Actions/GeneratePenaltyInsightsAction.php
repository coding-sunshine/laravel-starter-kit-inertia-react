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

        return Cache::remember($cacheKey, 86400, function () use ($sidingIds): ?array {
            if (! $this->prism->isAvailable()) {
                return null;
            }

            $data = $this->aggregateData($sidingIds);
            $prompt = $this->buildPrompt($data);

            try {
                $response = $this->prism->generate($prompt);
                $text = mb_trim($response->text);

                return $this->parseInsights($text);
            } catch (Throwable) {
                return null;
            }
        });
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

        return [
            'by_type' => $byType,
            'by_responsible' => $byResponsible,
            'by_siding' => $bySiding,
            'by_day_of_week' => $byDow,
            'monthly_trend' => $monthTotals,
            'disputed' => $disputed,
            'waived' => $waived,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildPrompt(array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);

        return <<<PROMPT
        You are RRMCS AI, a railway operations analyst. Analyze these penalty patterns from the last 3 months and provide exactly 5 actionable recommendations.

        Penalty data:
        {$json}

        For each recommendation, output it on its own line in this exact format:
        [SEVERITY] Title | Description

        Where SEVERITY is one of: HIGH, MEDIUM, LOW
        Title is a short actionable heading (under 60 chars).
        Description is 1-2 sentences explaining the insight and what to do about it.

        Focus on:
        - Which penalty types cost the most money and how to reduce them
        - Which sidings need attention and what they can do differently
        - Day-of-week patterns that suggest operational scheduling improvements
        - Dispute success rate and whether more penalties should be contested
        - Trend direction and whether things are improving or worsening

        Be specific with numbers and percentages. Use ₹ for currency.
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

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Services\PrismService;
use App\Support\BilledPenaltyQuery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\NumberSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;
use Throwable;

final readonly class GeneratePenaltyInsightsAction
{
    public function __construct(private PrismService $prism) {}

    /**
     * Generate AI-powered penalty insights for a set of sidings.
     * Cached for 24 hours; called weekly by scheduled command.
     *
     * @param  array<int>  $sidingIds
     * @return array<int, array{title: string, description: string, severity: string, estimated_savings_inr: float, category: string}>|null
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

        // Without billed penalties there is nothing to analyse; asking the model
        // anyway is how this feature produced confident recommendations from an
        // empty table for months.
        if ($data['total_count'] === 0) {
            Cache::put($cacheKey, '__unavailable__', 3600);

            return null;
        }

        $prompt = $this->buildPrompt($data);

        try {
            $response = $this->prism->structured($this->prism->fastModel())
                ->withPrompt($prompt)
                ->withSchema($this->buildSchema())
                ->asStructured();

            /** @var array{insights: array<int, array{severity: string, title: string, description: string, estimated_savings_inr: float, category: string}>} $structured */
            $structured = $response->structured;

            $insights = array_slice($structured['insights'] ?? [], 0, 5);
            Cache::put($cacheKey, $insights, 86400);

            return $insights;
        } catch (Throwable) {
            Cache::put($cacheKey, '__unavailable__', 3600);

            return null;
        }
    }

    private function buildSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'penalty_insights',
            description: 'AI-generated penalty insights with actionable recommendations',
            properties: [
                new ArraySchema(
                    name: 'insights',
                    description: 'List of 5 actionable cost-saving recommendations',
                    items: new ObjectSchema(
                        name: 'insight',
                        description: 'A single insight/recommendation',
                        properties: [
                            new EnumSchema(
                                name: 'severity',
                                description: 'Impact severity level',
                                options: ['high', 'medium', 'low'],
                            ),
                            new StringSchema(
                                name: 'title',
                                description: 'Short actionable heading under 60 characters',
                            ),
                            new StringSchema(
                                name: 'description',
                                description: '1-2 sentences explaining the insight with projected savings in INR',
                            ),
                            new NumberSchema(
                                name: 'estimated_savings_inr',
                                description: 'Estimated annual savings in INR',
                            ),
                            new StringSchema(
                                name: 'category',
                                description: 'Category: penalty_type, siding_hotspot, concentration, timing, top_lever',
                            ),
                        ],
                        requiredFields: ['severity', 'title', 'description', 'estimated_savings_inr', 'category'],
                    ),
                ),
            ],
            requiredFields: ['insights'],
        );
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<string, mixed>
     */
    private function aggregateData(array $sidingIds): array
    {
        $from = Carbon::now()->subMonths(3)->startOfDay();
        $rows = BilledPenaltyQuery::dated(BilledPenaltyQuery::between($sidingIds, $from));

        $byType = BilledPenaltyQuery::totalsByType(BilledPenaltyQuery::between($sidingIds, $from));
        $bySiding = array_slice(
            BilledPenaltyQuery::totalsBySiding(BilledPenaltyQuery::between($sidingIds, $from)),
            0,
            5
        );
        $bySidingAndType = array_slice(
            BilledPenaltyQuery::totalsBySidingAndType(BilledPenaltyQuery::between($sidingIds, $from)),
            0,
            10
        );

        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $byDow = [];
        $byMonth = [];

        foreach ($rows as $row) {
            if ($row->penalty_date === null) {
                continue;
            }

            $date = Carbon::parse((string) $row->penalty_date);
            $day = $dayNames[(int) $date->dayOfWeek];
            $byDow[$day] = ($byDow[$day] ?? 0) + 1;

            $month = $date->format('M Y');
            $byMonth[$month] = ($byMonth[$month] ?? 0) + (float) $row->amount;
        }

        arsort($byDow);

        $monthTotals = [];
        for ($i = 2; $i >= 0; $i--) {
            $label = Carbon::now()->subMonths($i)->format('M Y');
            $monthTotals[] = ['month' => $label, 'total' => round($byMonth[$label] ?? 0.0, 2)];
        }

        return [
            'total_count' => $rows->count(),
            'total_amount' => round((float) $rows->sum('amount'), 2),
            'by_type' => $byType,
            'by_siding' => $bySiding,
            'by_siding_and_type' => $bySidingAndType,
            'by_day_of_week' => array_map(
                fn (string $day, int $count): array => ['day' => $day, 'count' => $count],
                array_keys($byDow),
                array_values($byDow)
            ),
            'monthly_trend' => $monthTotals,
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

        These are penalties actually billed on RRs over the last 3 months: {$data['total_count']} charges worth ₹{$data['total_amount']}.

        Focus your analysis on these cost-saving opportunities:
        1. PENALTY TYPE TRENDS: Which types dominate and which are growing month over month? What specific operational change at loading or placement would reduce each?
        2. SIDING HOTSPOTS: Which sidings need intervention? What would bringing the worst siding to the median siding's rate per charge save over a year?
        3. TYPE-BY-SIDING CONCENTRATION: Where is one penalty type concentrated in one siding? Those are the cheapest to fix because one local change removes the whole cluster.
        4. TIMING: Do charges cluster on particular weekdays? If so, name the shift or staffing change that addresses it.
        5. THE SINGLE BIGGEST LEVER: If the team could only do one thing next month, what is it and what does it save?

        Be specific with numbers, percentages, and projected ₹ savings. Every recommendation must include a savings estimate derived from the figures above — do not invent data that is not in the JSON. Do not comment on disputes, waivers or responsible parties: that data is not tracked here.
        PROMPT;
    }
}

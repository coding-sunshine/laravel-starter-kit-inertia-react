<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\SidingRiskScoringAgent;
use App\Models\Penalty;
use App\Models\Siding;
use App\Models\SidingPerformance;
use App\Models\SidingRiskScore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class CalculateSidingRiskScoresAction
{
    /**
     * Calculate risk scores for all active sidings.
     *
     * @return int Number of scores created
     */
    public function handle(): int
    {
        $sidings = Siding::query()
            ->whereHas('rakes')
            ->get(['id', 'name', 'code']);

        if ($sidings->isEmpty()) {
            return 0;
        }

        $sidingIds = $sidings->pluck('id')->all();
        $data = $this->collectMetrics($sidingIds);

        if ($data === []) {
            return 0;
        }

        $prompt = $this->buildPrompt($data, $sidings);

        try {
            $agent = new SidingRiskScoringAgent;
            $response = $agent->prompt($prompt);

            /** @var array{scores: array<int, array{siding_name: string, risk_score: int, risk_factors: string[], trend: string}>} $structured */
            $structured = $response->toArray();
            $scores = $structured['scores'] ?? [];

            $sidingMap = $sidings->keyBy('name');
            $created = 0;
            $now = now();

            foreach ($scores as $score) {
                $siding = $sidingMap->get($score['siding_name']);
                if (! $siding) {
                    continue;
                }

                SidingRiskScore::query()->updateOrCreate(
                    [
                        'siding_id' => $siding->id,
                        'calculated_at' => $now->toDateString(),
                    ],
                    [
                        'score' => min(100, max(0, $score['risk_score'])),
                        'risk_factors' => $score['risk_factors'],
                        'trend' => $score['trend'],
                        'calculated_at' => $now,
                    ]
                );

                $created++;
            }

            return $created;
        } catch (Throwable $e) {
            Log::warning('Siding risk scoring failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<string, mixed>
     */
    private function collectMetrics(array $sidingIds): array
    {
        // Last 30 days penalties by siding
        $penaltiesThisMonth = Penalty::query()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->where('penalty_date', '>=', now()->subDays(30))
            ->selectRaw('sidings.name, count(*) as count, sum(penalty_amount) as total')
            ->groupBy('sidings.name')
            ->get()
            ->keyBy('name')
            ->all();

        // Previous 30 days for comparison
        $penaltiesLastMonth = Penalty::query()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->whereIn('rakes.siding_id', $sidingIds)
            ->where('penalty_date', '>=', now()->subDays(60))
            ->where('penalty_date', '<', now()->subDays(30))
            ->selectRaw('sidings.name, count(*) as count, sum(penalty_amount) as total')
            ->groupBy('sidings.name')
            ->get()
            ->keyBy('name')
            ->all();

        // Performance data (last 14 days)
        $performance = SidingPerformance::query()
            ->join('sidings', 'siding_performance.siding_id', '=', 'sidings.id')
            ->whereIn('siding_performance.siding_id', $sidingIds)
            ->where('as_of_date', '>=', now()->subDays(14))
            ->select(
                'sidings.name',
                DB::raw('sum(rakes_processed) as rakes'),
                DB::raw('avg(average_demurrage_hours) as avg_demurrage'),
                DB::raw('sum(overload_incidents) as overloads'),
            )
            ->groupBy('sidings.name')
            ->get()
            ->keyBy('name')
            ->all();

        $metrics = [];
        foreach ($sidingIds as $sidingId) {
            $siding = Siding::find($sidingId);
            if (! $siding) {
                continue;
            }

            $thisMonth = $penaltiesThisMonth[$siding->name] ?? null;
            $lastMonth = $penaltiesLastMonth[$siding->name] ?? null;
            $perf = $performance[$siding->name] ?? null;

            $metrics[] = [
                'siding' => $siding->name,
                'penalties_30d' => [
                    'count' => $thisMonth ? (int) $thisMonth->count : 0,
                    'total' => $thisMonth ? (float) $thisMonth->total : 0,
                ],
                'penalties_prev_30d' => [
                    'count' => $lastMonth ? (int) $lastMonth->count : 0,
                    'total' => $lastMonth ? (float) $lastMonth->total : 0,
                ],
                'performance_14d' => [
                    'rakes_processed' => $perf ? (int) $perf->rakes : 0,
                    'avg_demurrage_hours' => $perf ? round((float) $perf->avg_demurrage, 1) : 0,
                    'overload_incidents' => $perf ? (int) $perf->overloads : 0,
                ],
            ];
        }

        return $metrics;
    }

    /**
     * @param  array<int, array<string, mixed>>  $data
     * @param  \Illuminate\Database\Eloquent\Collection<int, Siding>  $sidings
     */
    private function buildPrompt(array $data, $sidings): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);

        return <<<PROMPT
        Calculate a composite risk score (0-100) for each of these coal sidings based on the following metrics.

        Scoring guidelines:
        - 0-25: Low risk (few penalties, good performance)
        - 26-50: Moderate risk (some penalties, room for improvement)
        - 51-75: High risk (frequent penalties, poor trends)
        - 76-100: Critical risk (escalating penalties, immediate attention needed)

        Key factors to weigh:
        - Penalty frequency relative to rakes processed (penalty rate)
        - Penalty amount trends (increasing = higher risk)
        - Average demurrage hours (higher = more risk)
        - Overload incidents (each adds risk)
        - Month-over-month change (worsening trends = higher risk)

        Siding metrics:
        {$json}
        PROMPT;
    }
}

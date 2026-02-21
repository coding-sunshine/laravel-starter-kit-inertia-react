<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\PenaltyPredictionAgent;
use App\Models\Penalty;
use App\Models\PenaltyPrediction;
use App\Models\Siding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class GeneratePenaltyPredictionsAction
{
    /**
     * Generate penalty predictions for all active sidings.
     *
     * @return int Number of predictions created
     */
    public function handle(): int
    {
        $sidings = Siding::query()
            ->whereHas('rakes')
            ->get(['id', 'name', 'code']);

        if ($sidings->isEmpty()) {
            return 0;
        }

        $data = $this->collectHistoricalData($sidings->pluck('id')->all());

        if ($data === []) {
            return 0;
        }

        $prompt = $this->buildPrompt($data, $sidings);

        try {
            $agent = new PenaltyPredictionAgent;
            $response = $agent->prompt($prompt);

            /** @var array{predictions: array<int, array{siding_name: string, risk_level: string, predicted_penalty_types: string[], predicted_amount_min: float, predicted_amount_max: float, contributing_factors: string[], recommended_actions: string[]}>} $structured */
            $structured = $response->toArray();
            $predictions = $structured['predictions'] ?? [];

            $sidingMap = $sidings->keyBy('name');
            $created = 0;
            $today = now()->toDateString();

            // Remove old predictions for today
            PenaltyPrediction::query()
                ->where('prediction_date', $today)
                ->delete();

            foreach ($predictions as $prediction) {
                $siding = $sidingMap->get($prediction['siding_name']);
                if (! $siding) {
                    continue;
                }

                PenaltyPrediction::query()->create([
                    'siding_id' => $siding->id,
                    'prediction_date' => $today,
                    'risk_level' => $prediction['risk_level'],
                    'predicted_types' => $prediction['predicted_penalty_types'],
                    'predicted_amount_min' => $prediction['predicted_amount_min'],
                    'predicted_amount_max' => $prediction['predicted_amount_max'],
                    'factors' => $prediction['contributing_factors'],
                    'recommendations' => $prediction['recommended_actions'],
                ]);

                $created++;
            }

            return $created;
        } catch (Throwable $e) {
            Log::warning('Penalty prediction generation failed', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * @param  array<int>  $sidingIds
     * @return array<string, mixed>
     */
    private function collectHistoricalData(array $sidingIds): array
    {
        $baseQuery = fn () => Penalty::query()
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->where('penalty_date', '>=', now()->subDays(90));

        $totalPenalties = $baseQuery()->count();
        if ($totalPenalties === 0) {
            return [];
        }

        // Weekly trend (last 12 weeks)
        $weeklyTrend = $baseQuery()
            ->selectRaw("date_trunc('week', penalty_date)::date as week_start, count(*) as count, sum(penalty_amount) as total")
            ->groupBy('week_start')
            ->orderBy('week_start')
            ->get()
            ->map(fn ($r): array => [
                'week' => $r->week_start,
                'count' => (int) $r->count,
                'total' => (float) $r->total,
            ])
            ->all();

        // By siding
        $bySiding = $baseQuery()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->selectRaw('sidings.name, count(*) as count, sum(penalty_amount) as total, avg(penalty_amount) as avg_amount')
            ->groupBy('sidings.name')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r): array => [
                'siding' => $r->name,
                'count' => (int) $r->count,
                'total' => (float) $r->total,
                'average' => round((float) $r->avg_amount, 2),
            ])
            ->all();

        // By type and siding
        $byTypeSiding = $baseQuery()
            ->join('rakes', 'penalties.rake_id', '=', 'rakes.id')
            ->join('sidings', 'rakes.siding_id', '=', 'sidings.id')
            ->selectRaw('sidings.name as siding, penalty_type, count(*) as count')
            ->groupBy('sidings.name', 'penalty_type')
            ->get()
            ->map(fn ($r): array => [
                'siding' => $r->siding,
                'type' => $r->penalty_type,
                'count' => (int) $r->count,
            ])
            ->all();

        // Day of week pattern
        $dowSql = DB::getDriverName() === 'pgsql'
            ? 'EXTRACT(DOW FROM penalty_date)::int'
            : 'DAYOFWEEK(penalty_date) - 1';

        $dayOfWeek = $baseQuery()
            ->selectRaw("{$dowSql} as dow, count(*) as count")
            ->groupBy('dow')
            ->orderBy('dow')
            ->get()
            ->map(fn ($r): array => [
                'day' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'][(int) $r->dow] ?? '?',
                'count' => (int) $r->count,
            ])
            ->all();

        // Recent root causes
        $rootCauses = $baseQuery()
            ->whereNotNull('root_cause')
            ->where('root_cause', '!=', '')
            ->where('penalty_date', '>=', now()->subDays(30))
            ->selectRaw('root_cause, count(*) as count')
            ->groupBy('root_cause')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'root_cause')
            ->all();

        return [
            'total_penalties_90d' => $totalPenalties,
            'weekly_trend' => $weeklyTrend,
            'by_siding' => $bySiding,
            'by_type_siding' => $byTypeSiding,
            'day_of_week_pattern' => $dayOfWeek,
            'recent_root_causes' => $rootCauses,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  \Illuminate\Database\Eloquent\Collection<int, Siding>  $sidings
     */
    private function buildPrompt(array $data, $sidings): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        $sidingNames = $sidings->pluck('name')->implode(', ');
        $today = now()->format('l, M d, Y');

        return <<<PROMPT
        Today is {$today}. Analyze the following 90-day historical penalty data for these coal sidings: {$sidingNames}.

        Predict the penalty risk for each siding over the next 7 days.

        Historical data:
        {$json}

        For each siding, provide:
        - risk_level (high/medium/low) based on recent trends and frequency
        - predicted_penalty_types most likely to occur
        - predicted_amount_min and predicted_amount_max based on historical averages
        - contributing_factors explaining why this risk level was assigned
        - recommended_actions to reduce the risk

        Consider:
        - Is the weekly penalty trend increasing or decreasing for this siding?
        - What day of week is it? Are penalties more common on certain days?
        - What root causes have been recurring recently?
        - How does this siding compare to others?
        PROMPT;
    }
}

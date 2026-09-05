<?php

declare(strict_types=1);

namespace App\Actions;

use App\Ai\Agents\PenaltyPredictionAgent;
use App\Models\PenaltyPrediction;
use App\Models\Siding;
use App\Support\BilledPenaltyQuery;
use Illuminate\Support\Carbon;
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
        $from = Carbon::now()->subDays(90)->startOfDay();
        $rows = BilledPenaltyQuery::dated(BilledPenaltyQuery::between($sidingIds, $from));

        if ($rows->isEmpty()) {
            return [];
        }

        $weekly = [];
        $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $dayOfWeekCounts = array_fill_keys($dayNames, 0);

        foreach ($rows as $row) {
            if ($row->penalty_date === null) {
                continue;
            }

            $date = Carbon::parse((string) $row->penalty_date);
            $week = $date->copy()->startOfWeek()->toDateString();
            $weekly[$week] ??= ['week' => $week, 'count' => 0, 'total' => 0.0];
            $weekly[$week]['count']++;
            $weekly[$week]['total'] += (float) $row->amount;

            $dayOfWeekCounts[$dayNames[(int) $date->dayOfWeek]]++;
        }

        ksort($weekly);

        foreach ($weekly as $week => $bucket) {
            $weekly[$week]['total'] = round($bucket['total'], 2);
        }

        return [
            'total_penalties_90d' => $rows->count(),
            'weekly_trend' => array_values($weekly),
            'by_siding' => BilledPenaltyQuery::totalsBySiding(BilledPenaltyQuery::between($sidingIds, $from)),
            'by_type_siding' => BilledPenaltyQuery::totalsBySidingAndType(BilledPenaltyQuery::between($sidingIds, $from)),
            'day_of_week_pattern' => array_map(
                fn (string $day, int $count): array => ['day' => $day, 'count' => $count],
                array_keys($dayOfWeekCounts),
                array_values($dayOfWeekCounts)
            ),
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
        - Which penalty types recur at this siding specifically (by_type_siding)?
        - How does this siding compare to others?

        Every figure you state must come from the data above. Dispute status, root cause and responsible party are not tracked; do not refer to them.
        PROMPT;
    }
}

<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\PenaltyPrediction;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

final class PredictionsTool implements Tool
{
    /**
     * @param  array<int>  $sidingIds
     */
    public function __construct(private readonly array $sidingIds) {}

    public function description(): Stringable|string
    {
        return 'Query AI-generated penalty risk predictions. Shows which sidings are at highest risk of penalties this week with predicted types, amounts, and recommended actions. Use this when users ask about future risks, forecasts, or predictions.';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = PenaltyPrediction::query()
            ->with('siding:id,name')
            ->whereIn('siding_id', $this->sidingIds)
            ->where('prediction_date', '>=', now()->subDays(7));

        if ($request['risk_level'] ?? null) {
            $query->where('risk_level', $request['risk_level']);
        }

        $predictions = $query
            ->latest('prediction_date')
            ->limit(20)
            ->get();

        if ($predictions->isEmpty()) {
            return json_encode([
                'message' => 'No predictions available yet. Run the daily prediction command to generate forecasts.',
                'predictions' => [],
            ], JSON_THROW_ON_ERROR);
        }

        return json_encode([
            'predictions' => $predictions->map(fn (PenaltyPrediction $p): array => [
                'siding' => $p->siding?->name,
                'prediction_date' => $p->prediction_date?->toDateString(),
                'risk_level' => $p->risk_level,
                'predicted_types' => $p->predicted_types,
                'amount_range' => [
                    'min' => (float) $p->predicted_amount_min,
                    'max' => (float) $p->predicted_amount_max,
                ],
                'factors' => $p->factors,
                'recommendations' => $p->recommendations,
            ])->all(),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'risk_level' => $schema->string()->enum(['high', 'medium', 'low'])->description('Filter by risk level.'),
        ];
    }
}

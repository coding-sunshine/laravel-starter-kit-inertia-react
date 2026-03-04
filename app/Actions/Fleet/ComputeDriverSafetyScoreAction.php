<?php

declare(strict_types=1);

namespace App\Actions\Fleet;

use App\Models\Fleet\Driver;
use Illuminate\Support\Facades\DB;

final readonly class ComputeDriverSafetyScoreAction
{
    private const int BEHAVIOR_PENALTY = 3;

    private const int INCIDENT_PENALTY = 10;

    private const int VIOLATION_PENALTY = 2;

    private const int ACCIDENT_PENALTY = 15;

    /**
     * Compute safety score (0–100) and risk category from behavior events, incidents, and stored counts.
     */
    public function handle(Driver $driver): array
    {
        $since = now()->subYear();

        $behaviorCount = $driver->behaviorEvents()->where('occurred_at', '>=', $since)->count();
        $incidentCount = $driver->incidents()->where('incident_date', '>=', $since)->count();
        $violations = (int) $driver->violations_count;
        $accidents = (int) $driver->accidents_count;

        $score = 100
            - ($behaviorCount * self::BEHAVIOR_PENALTY)
            - ($incidentCount * self::INCIDENT_PENALTY)
            - ($violations * self::VIOLATION_PENALTY)
            - ($accidents * self::ACCIDENT_PENALTY);

        $score = max(0, min(100, $score));
        $riskCategory = $score >= 80 ? 'low' : ($score >= 50 ? 'medium' : 'high');

        DB::transaction(function () use ($driver, $score, $riskCategory): void {
            $driver->update([
                'safety_score' => round($score, 2),
                'risk_category' => $riskCategory,
            ]);
        });

        return [
            'score' => round($score, 2),
            'risk_category' => $riskCategory,
        ];
    }
}

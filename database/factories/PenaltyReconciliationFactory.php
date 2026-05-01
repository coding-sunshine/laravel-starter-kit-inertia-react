<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PenaltyReconciliation;
use App\Models\Rake;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PenaltyReconciliation>
 */
final class PenaltyReconciliationFactory extends Factory
{
    protected $model = PenaltyReconciliation::class;

    public function definition(): array
    {
        $predicted = $this->faker->randomFloat(2, 1000, 50000);
        $billed = $this->faker->randomFloat(2, 1000, 50000);
        $variance = $billed - $predicted;
        $variancePct = $predicted > 0 ? ($variance / $predicted) * 100 : null;

        return [
            'rake_id' => Rake::factory(),
            'penalty_code' => $this->faker->randomElement(['DEM', 'PLO', 'POL1', 'POLA', 'ENHC']),
            'predicted_amount' => $predicted,
            'billed_amount' => $billed,
            'variance' => $variance,
            'variance_pct' => $variancePct,
            'dispute_candidate' => $variance > $predicted * 0.15,
            'notes' => null,
            'reconciled_at' => now(),
        ];
    }
}

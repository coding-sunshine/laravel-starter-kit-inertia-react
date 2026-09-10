<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Rake;
use App\Models\RrPenaltySnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RrPenaltySnapshot>
 */
final class RrPenaltySnapshotFactory extends Factory
{
    protected $model = RrPenaltySnapshot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rr_document_id' => null,
            'rake_id' => Rake::factory(),
            'rake_charge_id' => null,
            'penalty_code' => $this->faker->randomElement(['DEM', 'PLO', 'POL1', 'POLA', 'ENHC']),
            'amount' => $this->faker->randomFloat(2, 1000, 50000),
            'wagon_number' => null,
            'wagon_sequence' => null,
            'meta' => null,
        ];
    }
}

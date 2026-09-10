<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AppliedPenalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeCharge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppliedPenalty>
 */
final class AppliedPenaltyFactory extends Factory
{
    protected $model = AppliedPenalty::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'penalty_type_id' => PenaltyType::factory(),
            'rake_id' => Rake::factory(),
            'rake_charge_id' => null,
            'wagon_id' => null,
            'wagon_number' => null,
            'quantity' => $this->faker->randomFloat(2, 1, 24),
            'distance' => null,
            'rate' => $this->faker->randomFloat(2, 50, 500),
            'amount' => $this->faker->randomFloat(2, 100, 50000),
            'meta' => ['source' => 'manual'],
        ];
    }
}

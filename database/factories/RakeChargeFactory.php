<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Rake;
use App\Models\RakeCharge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RakeCharge>
 */
final class RakeChargeFactory extends Factory
{
    protected $model = RakeCharge::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rake_id' => Rake::factory(),
            'charge_type' => $this->faker->randomElement(['PENALTY', 'FREIGHT', 'OTHER']),
            'amount' => $this->faker->randomFloat(2, 0, 100000),
            'data_source' => 'predicted_penalty',
            'is_actual_charges' => false,
            'remarks' => null,
        ];
    }
}

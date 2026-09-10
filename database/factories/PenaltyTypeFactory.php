<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PenaltyType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PenaltyType>
 */
final class PenaltyTypeFactory extends Factory
{
    protected $model = PenaltyType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => mb_strtoupper($this->faker->unique()->bothify('???')),
            'name' => $this->faker->words(3, true),
            'category' => $this->faker->randomElement(['overloading', 'time_service', 'operational', 'safety', 'other']),
            'description' => $this->faker->sentence(),
            'calculation_type' => $this->faker->randomElement(['formula_based', 'fixed', 'per_hour', 'per_mt']),
            'default_rate' => $this->faker->randomFloat(2, 50, 500),
            'is_active' => true,
        ];
    }
}

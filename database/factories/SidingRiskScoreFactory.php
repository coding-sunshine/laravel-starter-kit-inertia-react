<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Siding;
use App\Models\SidingRiskScore;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SidingRiskScore>
 */
final class SidingRiskScoreFactory extends Factory
{
    protected $model = SidingRiskScore::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siding_id' => Siding::factory(),
            'score' => $this->faker->numberBetween(0, 100),
            'risk_factors' => $this->faker->randomElements([
                'High penalty frequency',
                'Increasing demurrage hours',
                'Overload incidents',
                'Equipment reliability issues',
                'Documentation delays',
                'Labour shortages',
            ], $this->faker->numberBetween(1, 4)),
            'trend' => $this->faker->randomElement(['improving', 'stable', 'worsening']),
            'calculated_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
        ];
    }

    public function critical(): self
    {
        return $this->state(fn (): array => [
            'score' => $this->faker->numberBetween(76, 100),
            'trend' => 'worsening',
        ]);
    }
}

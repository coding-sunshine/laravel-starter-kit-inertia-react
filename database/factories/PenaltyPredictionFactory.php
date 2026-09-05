<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PenaltyPrediction;
use App\Models\Siding;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PenaltyPrediction>
 */
final class PenaltyPredictionFactory extends Factory
{
    protected $model = PenaltyPrediction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'siding_id' => Siding::factory(),
            'prediction_date' => $this->faker->dateTimeBetween('now', '+7 days'),
            'risk_level' => $this->faker->randomElement(['high', 'medium', 'low']),
            'predicted_types' => $this->faker->randomElements(['DEM', 'POL1', 'POLA', 'PLO', 'ULC'], $this->faker->numberBetween(1, 3)),
            'predicted_amount_min' => $min = $this->faker->randomFloat(2, 1000, 50000),
            'predicted_amount_max' => $min + $this->faker->randomFloat(2, 5000, 50000),
            'factors' => $this->faker->randomElements([
                'High demurrage hours trend',
                'Increasing penalty frequency',
                'Equipment maintenance overdue',
                'Seasonal weather patterns',
                'Labour shortage indicators',
            ], $this->faker->numberBetween(1, 3)),
            'recommendations' => $this->faker->randomElements([
                'Schedule preventive equipment maintenance',
                'Increase loading crew availability',
                'Pre-position documentation requirements',
                'Coordinate with railway for timely placement',
                'Monitor weather forecasts and plan accordingly',
            ], $this->faker->numberBetween(1, 3)),
        ];
    }

    public function highRisk(): self
    {
        return $this->state(fn (): array => [
            'risk_level' => 'high',
            'predicted_amount_min' => 50000,
            'predicted_amount_max' => 150000,
        ]);
    }
}

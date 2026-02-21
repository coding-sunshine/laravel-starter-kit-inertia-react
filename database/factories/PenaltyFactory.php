<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Penalty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Penalty>
 */
final class PenaltyFactory extends Factory
{
    protected $model = Penalty::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rake_id' => fake()->numberBetween(1, 100),
            'penalty_type' => fake()->randomElement(['DEM', 'POL1', 'POLA', 'PLO', 'ULC', 'SPL', 'WMC', 'MCF']),
            'penalty_amount' => fake()->randomFloat(2, 500, 100000),
            'penalty_status' => fake()->randomElement(['pending', 'incurred', 'waived', 'disputed']),
            'responsible_party' => fake()->optional(0.7)->randomElement(['railway', 'siding', 'transporter', 'plant', 'other']),
            'root_cause' => fake()->optional(0.5)->sentence(),
            'description' => fake()->optional(0.6)->sentence(),
            'remediation_notes' => null,
            'penalty_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'calculation_breakdown' => null,
        ];
    }

    public function demurrage(): self
    {
        return $this->state(fn (): array => [
            'penalty_type' => 'DEM',
            'calculation_breakdown' => [
                'formula' => 'demurrage_hours × weight_mt × rate_per_mt_hour',
                'demurrage_hours' => $hours = fake()->randomFloat(1, 0.5, 24),
                'weight_mt' => $weight = fake()->randomFloat(0, 500, 3000),
                'rate_per_mt_hour' => 50,
                'free_hours' => 3.0,
                'dwell_hours' => $hours + 3.0,
            ],
        ]);
    }

    public function disputed(): self
    {
        return $this->state(fn (): array => [
            'penalty_status' => 'disputed',
            'disputed_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'dispute_reason' => fake()->sentence(),
        ]);
    }

    public function resolved(): self
    {
        return $this->state(fn (): array => [
            'penalty_status' => 'waived',
            'disputed_at' => fake()->dateTimeBetween('-60 days', '-30 days'),
            'dispute_reason' => fake()->sentence(),
            'resolved_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'resolution_notes' => fake()->sentence(),
        ]);
    }

    public function withResponsibility(): self
    {
        return $this->state(fn (): array => [
            'responsible_party' => fake()->randomElement(['railway', 'siding', 'transporter', 'plant']),
            'root_cause' => fake()->sentence(),
        ]);
    }
}

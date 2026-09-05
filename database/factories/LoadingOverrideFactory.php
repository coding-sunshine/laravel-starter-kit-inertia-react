<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LoadingOverride;
use App\Models\Rake;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoadingOverride>
 */
final class LoadingOverrideFactory extends Factory
{
    protected $model = LoadingOverride::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rake_id' => Rake::factory(),
            'wagon_loading_id' => null,
            'operator_id' => User::factory(),
            'reason' => $this->faker->randomElement(['reduced_load', 'equipment_constraint', 'railway_instruction', 'other']),
            'notes' => null,
            'overload_mt' => 0,
            'estimated_penalty_at_time' => 0,
            'supervisor_review_at' => null,
        ];
    }

    /**
     * Mark the override as reviewed by a supervisor.
     */
    public function reviewed(): static
    {
        return $this->state(fn (): array => [
            'supervisor_review_at' => now(),
        ]);
    }

    /**
     * Mark the override as pending (no supervisor review yet).
     */
    public function pending(): static
    {
        return $this->state(fn (): array => [
            'supervisor_review_at' => null,
        ]);
    }
}

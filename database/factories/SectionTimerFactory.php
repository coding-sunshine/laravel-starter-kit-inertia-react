<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SectionTimer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SectionTimer>
 */
final class SectionTimerFactory extends Factory
{
    protected $model = SectionTimer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_name' => $this->faker->randomElement(['loading', 'weighment', 'guard']),
            'free_minutes' => $this->faker->numberBetween(60, 480),
            'warning_minutes' => $this->faker->numberBetween(30, 60),
            'penalty_applicable' => true,
        ];
    }
}

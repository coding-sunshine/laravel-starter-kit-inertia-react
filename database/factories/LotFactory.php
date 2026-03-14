<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Lot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lot>
 */
final class LotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => null,
            'title' => $this->faker->optional()->words(3, true),
            'stage' => $this->faker->optional()->word(),
            'level' => $this->faker->optional()->word(),
            'price' => $this->faker->numberBetween(300000, 800000),
            'bedrooms' => $this->faker->numberBetween(1, 4),
            'bathrooms' => $this->faker->numberBetween(1, 3),
            'car' => $this->faker->numberBetween(0, 2),
            'title_status' => 'available',
            'is_archived' => false,
        ];
    }
}

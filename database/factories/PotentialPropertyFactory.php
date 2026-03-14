<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PotentialProperty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PotentialProperty>
 */
final class PotentialPropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->company(),
            'suburb' => $this->faker->city(),
            'state' => $this->faker->randomElement(['NSW', 'VIC', 'QLD', 'WA', 'SA', 'TAS', 'ACT', 'NT']),
            'status' => 'evaluating',
            'imported_from_csv' => false,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Projecttype;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Projecttype>
 */
final class ProjecttypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['House', 'Apartment', 'Land', 'Townhouse', 'Villa', 'Unit']),
        ];
    }
}

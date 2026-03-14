<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PropertySearch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertySearch>
 */
final class PropertySearchFactory extends Factory
{
    protected $model = PropertySearch::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'budget_min' => fake()->optional()->randomFloat(2, 200000, 500000),
            'budget_max' => fake()->optional()->randomFloat(2, 500000, 1500000),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

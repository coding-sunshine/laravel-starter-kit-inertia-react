<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LoaderOperator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoaderOperator>
 */
final class LoaderOperatorFactory extends Factory
{
    protected $model = LoaderOperator::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->name(),
            'is_active' => true,
            'siding_id' => null,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}

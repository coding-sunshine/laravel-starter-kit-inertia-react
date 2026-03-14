<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FlyerTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FlyerTemplate>
 */
final class FlyerTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Template '.$this->faker->numberBetween(1, 10),
            'is_active' => true,
            'legacy_id' => null,
        ];
    }
}

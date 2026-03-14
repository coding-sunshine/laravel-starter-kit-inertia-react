<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CustomField;
use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomFieldValue>
 */
final class CustomFieldValueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'custom_field_id' => CustomField::factory(),
            'entity_type' => $this->faker->randomElement(['contact', 'sale', 'lot', 'project']),
            'entity_id' => $this->faker->numberBetween(1, 100),
            'value' => $this->faker->optional()->word(),
        ];
    }
}

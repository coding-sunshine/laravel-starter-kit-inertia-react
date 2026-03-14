<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CustomField;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomField>
 */
final class CustomFieldFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'organization_id' => 1,
            'entity_type' => $this->faker->randomElement(['contact', 'sale', 'lot', 'project']),
            'name' => ucfirst($name),
            'key' => Str::slug($name, '_'),
            'type' => $this->faker->randomElement(['text', 'number', 'date', 'select', 'checkbox', 'url']),
            'options' => null,
            'is_required' => $this->faker->boolean(20),
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}

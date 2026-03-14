<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
final class ProjectFactory extends Factory
{
    public function definition(): array
    {
        $stages = ['pre_launch', 'selling', 'completed', 'archived'];

        return [
            'title' => $this->faker->company().' '.ucfirst($this->faker->word()).' Estate',
            'stage' => $this->faker->randomElement($stages),
            'suburb' => $this->faker->city(),
            'state' => $this->faker->randomElement(['NSW', 'VIC', 'QLD', 'WA', 'SA', 'TAS', 'ACT', 'NT']),
            'postcode' => $this->faker->numerify('####'),
            'total_lots' => $this->faker->numberBetween(10, 200),
            'min_price' => $this->faker->randomFloat(2, 200000, 600000),
            'max_price' => $this->faker->randomFloat(2, 600000, 1500000),
            'is_hot_property' => $this->faker->boolean(10),
            'is_archived' => false,
            'is_featured' => $this->faker->boolean(10),
            'is_smsf' => $this->faker->boolean(20),
            'is_firb' => $this->faker->boolean(10),
            'description' => $this->faker->paragraphs(3, true),
            'lat' => $this->faker->latitude(-44.0, -10.0),
            'lng' => $this->faker->longitude(113.0, 154.0),
        ];
    }
}

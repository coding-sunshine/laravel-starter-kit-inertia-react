<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<State>
 */
final class StateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $states = [
            ['name' => 'New South Wales', 'abbreviation' => 'NSW'],
            ['name' => 'Victoria', 'abbreviation' => 'VIC'],
            ['name' => 'Queensland', 'abbreviation' => 'QLD'],
            ['name' => 'Western Australia', 'abbreviation' => 'WA'],
            ['name' => 'South Australia', 'abbreviation' => 'SA'],
            ['name' => 'Tasmania', 'abbreviation' => 'TAS'],
            ['name' => 'Australian Capital Territory', 'abbreviation' => 'ACT'],
            ['name' => 'Northern Territory', 'abbreviation' => 'NT'],
        ];

        $state = $this->faker->randomElement($states);

        return [
            'name' => $state['name'],
            'abbreviation' => $state['abbreviation'],
            'country' => 'AU',
            'legacy_id' => null,
        ];
    }
}

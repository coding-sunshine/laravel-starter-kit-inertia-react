<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Suburb;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Suburb>
 */
final class SuburbFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->city(),
            'postcode' => mb_str_pad((string) $this->faker->numberBetween(1000, 9999), 4, '0', STR_PAD_LEFT),
            'state_id' => null,
            'legacy_id' => null,
        ];
    }
}

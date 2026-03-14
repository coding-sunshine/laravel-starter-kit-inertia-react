<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SprRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SprRequest>
 */
final class SprRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'state' => null,
            'spr_price' => 55.00,
            'payment_status' => 'pending',
            'request_status' => 'pending',
            'legacy_id' => null,
        ];
    }
}

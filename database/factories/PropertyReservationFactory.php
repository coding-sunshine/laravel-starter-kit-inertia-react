<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PropertyReservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyReservation>
 */
final class PropertyReservationFactory extends Factory
{
    protected $model = PropertyReservation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stage' => fake()->randomElement(['enquiry', 'qualified', 'reservation', 'contract', 'unconditional', 'settled']),
            'purchase_price' => fake()->randomFloat(2, 300000, 1500000),
            'deposit_status' => fake()->randomElement(['pending', 'paid', 'failed']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

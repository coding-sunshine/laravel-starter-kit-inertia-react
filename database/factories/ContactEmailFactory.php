<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactEmail;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactEmail>
 */
final class ContactEmailFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'type' => $this->faker->randomElement(['work', 'home', 'other']),
            'value' => $this->faker->unique()->safeEmail(),
            'is_primary' => false,
            'order_column' => 0,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\ContactPhone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactPhone>
 */
final class ContactPhoneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'type' => $this->faker->randomElement(['mobile', 'work', 'home']),
            'value' => $this->faker->phoneNumber(),
            'is_primary' => false,
            'order_column' => 0,
        ];
    }
}

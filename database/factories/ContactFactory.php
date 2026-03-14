<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
final class ContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_origin' => $this->faker->randomElement(['property', 'saas_product']),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'job_title' => $this->faker->optional()->jobTitle(),
            'type' => $this->faker->randomElement(['lead', 'client', 'partner', 'affiliate']),
            'stage' => $this->faker->optional()->randomElement(['new', 'nurture', 'hot', 'not_interested', 'property_reserved']),
            'company_name' => $this->faker->optional()->company(),
            'lead_score' => $this->faker->optional()->numberBetween(0, 100),
            'last_contacted_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'next_followup_at' => $this->faker->optional()->dateTimeBetween('now', '+3 months'),
        ];
    }
}

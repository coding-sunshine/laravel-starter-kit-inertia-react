<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
final class ContactFactory extends Factory
{
    protected $model = Contact::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'job_title' => fake()->optional()->jobTitle(),
            'type' => fake()->randomElement(['lead', 'client', 'agent', 'partner', 'subscriber', 'bdm', 'affiliate']),
            'stage' => fake()->optional()->randomElement(['new', 'qualified', 'converted', 'lost']),
            'company_name' => fake()->optional()->company(),
            'extra_attributes' => null,
        ];
    }

    public function lead(): static
    {
        return $this->state(['type' => 'lead', 'stage' => 'new']);
    }

    public function client(): static
    {
        return $this->state(['type' => 'client', 'stage' => 'converted']);
    }

    public function agent(): static
    {
        return $this->state(['type' => 'agent']);
    }

    public function partner(): static
    {
        return $this->state(['type' => 'partner']);
    }

    public function withSource(): static
    {
        return $this->state(fn (): array => [
            'source_id' => Source::factory(),
        ]);
    }

    public function withCompany(): static
    {
        return $this->state(fn (): array => [
            'company_id' => Company::factory(),
        ]);
    }
}

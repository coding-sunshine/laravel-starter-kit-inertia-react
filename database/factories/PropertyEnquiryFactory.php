<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PropertyEnquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyEnquiry>
 */
final class PropertyEnquiryFactory extends Factory
{
    protected $model = PropertyEnquiry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => fake()->randomElement(['new', 'contacted', 'qualified', 'closed']),
            'message' => fake()->optional()->paragraph(),
        ];
    }
}

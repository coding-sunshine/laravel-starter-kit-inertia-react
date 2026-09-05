<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EnterpriseInquiry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnterpriseInquiry>
 */
final class EnterpriseInquiryFactory extends Factory
{
    protected $model = EnterpriseInquiry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'company' => $this->faker->company(),
            'phone' => $this->faker->phoneNumber(),
            'message' => $this->faker->paragraphs(2, true),
            'status' => 'new',
        ];
    }
}

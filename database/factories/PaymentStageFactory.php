<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PaymentStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentStage>
 */
final class PaymentStageFactory extends Factory
{
    protected $model = PaymentStage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stage' => fake()->randomElement(['eoi', 'deposit', 'commission', 'payout']),
            'amount' => fake()->optional()->randomFloat(2, 1000, 100000),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+6 months')?->format('Y-m-d'),
            'paid_at' => fake()->optional()->dateTimeBetween('-6 months', 'now'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}

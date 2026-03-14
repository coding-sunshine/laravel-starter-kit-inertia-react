<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Commission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commission>
 */
final class CommissionFactory extends Factory
{
    protected $model = Commission::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commission_type' => fake()->randomElement([
                Commission::TYPE_PIAB,
                Commission::TYPE_SUBSCRIBER,
                Commission::TYPE_AFFILIATE,
                Commission::TYPE_SALES_AGENT,
                Commission::TYPE_REFERRAL_PARTNER,
                Commission::TYPE_BDM,
                Commission::TYPE_SUB_AGENT,
            ]),
            'rate_percentage' => fake()->optional()->randomFloat(2, 0.5, 5.0),
            'amount' => fake()->randomFloat(2, 0, 50000),
            'override_amount' => false,
        ];
    }
}

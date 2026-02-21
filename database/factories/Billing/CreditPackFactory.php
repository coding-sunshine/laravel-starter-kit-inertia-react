<?php

declare(strict_types=1);

namespace Database\Factories\Billing;

use App\Models\Billing\CreditPack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CreditPack>
 */
final class CreditPackFactory extends Factory
{
    protected $model = CreditPack::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->unique()->slug(),
            'description' => $this->faker->sentence(),
            'credits' => $this->faker->numberBetween(10, 500),
            'bonus_credits' => 0,
            'price' => $this->faker->numberBetween(500, 50000),
            'currency' => 'usd',
            'validity_days' => 365,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}

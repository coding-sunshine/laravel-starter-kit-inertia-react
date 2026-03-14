<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
final class SaleFactory extends Factory
{
    protected $model = Sale::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => fake()->randomElement(['active', 'unconditional', 'settled', 'cancelled']),
            'is_comments_enabled' => true,
            'is_sas_enabled' => false,
            'is_sas_max' => false,
        ];
    }
}

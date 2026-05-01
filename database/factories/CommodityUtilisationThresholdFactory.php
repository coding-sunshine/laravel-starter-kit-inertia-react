<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CommodityUtilisationThreshold;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CommodityUtilisationThreshold> */
final class CommodityUtilisationThresholdFactory extends Factory
{
    protected $model = CommodityUtilisationThreshold::class;

    public function definition(): array
    {
        return [
            'commodity_grade' => $this->faker->randomElement(['G1', 'G2', 'G3', 'G4', 'G5']),
            'utilisation_threshold' => 0.95,
            'effective_from' => now()->subYear(),
            'effective_to' => null,
            'source' => 'seeded default',
        ];
    }
}

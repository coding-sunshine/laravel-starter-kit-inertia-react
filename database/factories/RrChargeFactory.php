<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RrCharge;
use App\Models\RrDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RrCharge>
 */
final class RrChargeFactory extends Factory
{
    protected $model = RrCharge::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rr_document_id' => RrDocument::factory(),
            'rake_charge_id' => null,
            'charge_code' => 'FREIGHT',
            'charge_name' => 'Freight',
            'amount' => $this->faker->randomFloat(2, 1000, 50000),
        ];
    }
}

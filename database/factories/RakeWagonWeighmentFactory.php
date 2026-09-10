<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RakeWagonWeighment;
use App\Models\RakeWeighment;
use App\Models\Wagon;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RakeWagonWeighment> */
final class RakeWagonWeighmentFactory extends Factory
{
    protected $model = RakeWagonWeighment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'rake_weighment_id' => RakeWeighment::factory(),
            'wagon_id' => Wagon::factory(),
            'wagon_number' => $this->faker->bothify('WGN-####'),
            'wagon_sequence' => null,
            'wagon_type' => null,
            'axles' => null,
            'cc_capacity_mt' => 70.0,
            'printed_tare_mt' => null,
            'actual_gross_mt' => null,
            'actual_tare_mt' => null,
            'net_weight_mt' => 60.0,
            'under_load_mt' => null,
            'over_load_mt' => null,
            'speed_kmph' => null,
            'weighment_time' => null,
            'slip_number' => null,
            'action_taken' => null,
        ];
    }
}

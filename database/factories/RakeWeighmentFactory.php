<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Rake;
use App\Models\RakeWeighment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RakeWeighment> */
final class RakeWeighmentFactory extends Factory
{
    protected $model = RakeWeighment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'rake_id' => Rake::factory(),
            'attempt_no' => 1,
            'gross_weighment_datetime' => now(),
            'tare_weighment_datetime' => now(),
            'commodity' => null,
            'from_station' => null,
            'to_station' => null,
            'priority_number' => null,
            'total_gross_weight_mt' => null,
            'total_tare_weight_mt' => null,
            'total_net_weight_mt' => null,
            'total_cc_weight_mt' => null,
            'total_under_load_mt' => null,
            'total_over_load_mt' => null,
            'maximum_train_speed_kmph' => null,
            'maximum_weight_mt' => null,
            'pdf_file_path' => null,
            'status' => 'imported',
            'created_by' => null,
        ];
    }
}

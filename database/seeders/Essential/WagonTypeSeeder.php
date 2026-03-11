<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Models\WagonType;
use Illuminate\Database\Seeder;

final class WagonTypeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'code' => 'BOXN',
                'full_form' => 'Bogie Open eXtra capacity',
                'typical_use' => 'Thermal coal, bulk coal',
                'loading_method' => 'Top loading',
                'carrying_capacity_min_mt' => 63,
                'carrying_capacity_max_mt' => 65,
                'gross_tare_weight_mt' => 23,
                'default_pcc_weight_mt' => 64,
            ],
            [
                'code' => 'BOXNHL',
                'full_form' => 'BOXN High Load',
                'typical_use' => 'High-density coal',
                'loading_method' => 'Top loading',
                'carrying_capacity_min_mt' => 70,
                'carrying_capacity_max_mt' => 72,
                'gross_tare_weight_mt' => 25,
                'default_pcc_weight_mt' => 71,
            ],
            [
                'code' => 'BOBRN',
                'full_form' => 'Bogie Open Bottom Rapid',
                'typical_use' => 'Power plants (fast unloading)',
                'loading_method' => 'Bottom discharge',
                'carrying_capacity_min_mt' => 68,
                'carrying_capacity_max_mt' => 70,
                'gross_tare_weight_mt' => 24,
                'default_pcc_weight_mt' => 69,
            ],
            [
                'code' => 'BOBYN',
                'full_form' => 'Bogie Open Bottom (New)',
                'typical_use' => 'High-capacity coal rakes',
                'loading_method' => 'Bottom discharge',
                'carrying_capacity_min_mt' => 72,
                'carrying_capacity_max_mt' => 75,
                'gross_tare_weight_mt' => 25,
                'default_pcc_weight_mt' => 73.5,
            ],
        ];

        foreach ($rows as $data) {
            WagonType::query()->firstOrCreate(
                ['code' => $data['code']],
                $data
            );
        }
    }
}

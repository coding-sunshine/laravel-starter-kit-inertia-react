<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Models\PenaltyType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PenaltyTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $penaltyTypes = [
            [
                'code' => 'POL1',
                'name' => 'Punitive Overloading (Individual Wagon)',
                'category' => 'overloading',
                'calculation_type' => 'formula_based',
                'description' => 'Penalty for excess weight in individual wagons',
                'default_rate' => 0.00,
                'is_active' => true,
            ],
            [
                'code' => 'POLA',
                'name' => 'Punitive Overloading (Average)',
                'category' => 'overloading',
                'calculation_type' => 'formula_based',
                'description' => 'Penalty for average overloading across wagons',
                'default_rate' => 0.00,
                'is_active' => true,
            ],
            [
                'code' => 'PLO',
                'name' => 'Penal Loading Overcharge',
                'category' => 'overloading',
                'calculation_type' => 'per_mt',
                'description' => 'Charge for penal loading excess weight',
                'default_rate' => 0.00,
                'is_active' => true,
            ],
            [
                'code' => 'DEM',
                'name' => 'Demurrage',
                'category' => 'time_service',
                'calculation_type' => 'per_hour',
                'description' => 'Charge for delay beyond free time',
                'default_rate' => 0.00,
                'is_active' => true,
            ],
            [
                'code' => 'WMC',
                'name' => 'Weighment Charge',
                'category' => 'operational',
                'calculation_type' => 'fixed',
                'description' => 'Charge for weighment services',
                'default_rate' => 0.00,
                'is_active' => true,
            ],
            [
                'code' => 'ULC',
                'name' => 'Uneven Loading Charge',
                'category' => 'operational',
                'calculation_type' => 'fixed',
                'description' => 'Charge for uneven loading of wagons',
                'default_rate' => 0.00,
                'is_active' => true,
            ],
            [
                'code' => 'SPL',
                'name' => 'Spillage / Loose Loading',
                'category' => 'operational',
                'calculation_type' => 'per_mt',
                'description' => 'Charge for spillage or loose loading',
                'default_rate' => 0.00,
                'is_active' => true,
            ],
            [
                'code' => 'MCF',
                'name' => 'Moisture Content Factor',
                'category' => 'operational',
                'calculation_type' => 'formula_based',
                'description' => 'Adjustment factor for moisture content',
                'default_rate' => 0.00,
                'is_active' => true,
            ],
        ];

        foreach ($penaltyTypes as $type) {
            PenaltyType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}

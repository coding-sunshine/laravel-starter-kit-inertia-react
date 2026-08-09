<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Models\PenaltyType;
use Illuminate\Database\Seeder;

final class PenaltyTypesSeeder extends Seeder
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
                'default_rate' => 1500.00,
                'is_active' => true,
            ],
            [
                'code' => 'POLA',
                'name' => 'Punitive Overloading (Average)',
                'category' => 'overloading',
                'calculation_type' => 'formula_based',
                'description' => 'Penalty for average overloading across wagons',
                'default_rate' => 1200.00,
                'is_active' => true,
            ],
            [
                'code' => 'POL',
                'name' => 'Punitive Overloading',
                'category' => 'overloading',
                'calculation_type' => 'formula_based',
                'description' => 'Punitive overloading charge (unsuffixed RR code)',
                'default_rate' => 0.00,
                'is_active' => true,
            ],
            [
                'code' => 'POL2',
                'name' => 'Punitive Overloading Charge',
                'category' => 'overloading',
                'calculation_type' => 'per_mt',
                'description' => 'Penalty for wagon overloading when the total rake payload also exceeds the permissible carrying capacity of the rake',
                'default_rate' => 0.00,
                'is_active' => true,
            ],
            [
                'code' => 'PCLA',
                'name' => 'Punitive Charges for Load Adjustment',
                'category' => 'time_service',
                'calculation_type' => 'per_hour',
                'description' => 'Detention of total wagons of the rake for the time period required for load adjustment',
                'default_rate' => 0.00,
                'is_active' => true,
            ],
            [
                'code' => 'DCLA',
                'name' => 'Detention Charge For Load Adjustment',
                'category' => 'time_service',
                'calculation_type' => 'per_hour',
                'description' => 'Penal charge when wagons are detained beyond the permissible free time for loading',
                'default_rate' => 0.00,
                'is_active' => true,
            ],
            [
                'code' => 'FAUC',
                'name' => 'Freight adjustment-Undercharge',
                'category' => 'operational',
                'calculation_type' => 'fixed',
                'description' => 'Commercial adjustment made when the freight originally collected is less than what should have been charged',
                'default_rate' => 0.00,
                'is_active' => true,
            ],
            [
                'code' => 'ENHC',
                'name' => 'Engine Hire Charges',
                'category' => 'time_service',
                'calculation_type' => 'per_hour',
                'description' => 'Charges for use of a locomotive during operations outside the normal transportation service (load adjustment/demurrage)',
                'default_rate' => 0.00,
                'is_active' => true,
            ],
            [
                'code' => 'PLO',
                'name' => 'Penal Loading Overcharge',
                'category' => 'overloading',
                'calculation_type' => 'per_mt',
                'description' => 'Charge for penal loading excess weight',
                'default_rate' => 100.00,
                'is_active' => true,
            ],
            [
                'code' => 'DEM',
                'name' => 'Demurrage',
                'category' => 'time_service',
                'calculation_type' => 'per_hour',
                'description' => 'Charge for delay beyond free time',
                'default_rate' => 50.00,
                'is_active' => true,
            ],
            [
                'code' => 'WMC',
                'name' => 'Weighment Charge',
                'category' => 'operational',
                'calculation_type' => 'fixed',
                'description' => 'Charge for weighment services',
                'default_rate' => 20.00,
                'is_active' => true,
            ],
            [
                'code' => 'ULC',
                'name' => 'Uneven Loading Charge',
                'category' => 'operational',
                'calculation_type' => 'fixed',
                'description' => 'Charge for uneven loading of wagons',
                'default_rate' => 200.00,
                'is_active' => true,
            ],
            [
                'code' => 'SPL',
                'name' => 'Spillage / Loose Loading',
                'category' => 'operational',
                'calculation_type' => 'per_mt',
                'description' => 'Charge for spillage or loose loading',
                'default_rate' => 150.00,
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

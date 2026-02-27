<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Models\PenaltyType;
use Illuminate\Database\Seeder;

final class PenaltyTypeSeeder extends Seeder
{
    /**
     * Optional: define dependencies if needed
     */
    protected array $dependencies = [];

    public function run(): void
    {
        $penalties = [

            // =============================
            // OVERLOADING
            // =============================

            [
                'code' => 'POL1',
                'name' => 'Punitive Overloading (Individual Wagon)',
                'category' => 'overloading',
                'description' => 'Single wagon exceeds PCC.',
                'calculation_type' => 'excess_mt_distance_rate',
                'is_active' => true,
            ],

            [
                'code' => 'POLA',
                'name' => 'Punitive Overloading (Average)',
                'category' => 'overloading',
                'description' => 'Average rake load exceeds PCC.',
                'calculation_type' => 'average_overload',
                'is_active' => true,
            ],

            [
                'code' => 'PLO',
                'name' => 'Penal Loading Overcharge',
                'category' => 'overloading',
                'description' => 'Load above base weight but within permissible limit.',
                'calculation_type' => 'proportionate_loading',
                'is_active' => true,
            ],

            // =============================
            // TIME & SERVICE
            // =============================

            [
                'code' => 'DEM',
                'name' => 'Demurrage',
                'category' => 'time_service',
                'description' => 'Delay beyond free loading/unloading time.',
                'calculation_type' => 'per_wagon_per_hour',
                'is_active' => true,
            ],

            [
                'code' => 'WMC',
                'name' => 'Weighment Charge',
                'category' => 'time_service',
                'description' => 'Weighbridge usage charge.',
                'calculation_type' => 'fixed',
                'is_active' => true,
            ],

            // =============================
            // OPERATIONAL / SAFETY
            // =============================

            [
                'code' => 'ULC',
                'name' => 'Uneven Loading Charge',
                'category' => 'operational',
                'description' => 'Axle load imbalance detected.',
                'calculation_type' => 'custom',
                'is_active' => true,
            ],

            [
                'code' => 'SPL',
                'name' => 'Spillage / Loose Loading',
                'category' => 'safety',
                'description' => 'Material spilling or over-rim loading.',
                'calculation_type' => 'custom',
                'is_active' => true,
            ],

            [
                'code' => 'MCF',
                'name' => 'Moisture Content Factor',
                'category' => 'other',
                'description' => 'Weight variation due to moisture content.',
                'calculation_type' => 'custom',
                'is_active' => true,
            ],
        ];

        foreach ($penalties as $penalty) {
            PenaltyType::updateOrCreate(
                ['code' => $penalty['code']],
                $penalty
            );
        }
    }
}

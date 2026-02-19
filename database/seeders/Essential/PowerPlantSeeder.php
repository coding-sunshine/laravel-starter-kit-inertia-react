<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Models\PowerPlant;
use Illuminate\Database\Seeder;

final class PowerPlantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates power plants for reconciliation (RR vs Power Plant receipt).
     */
    public function run(): void
    {
        $plants = [
            [
                'name' => 'NTPC Kahalgaon',
                'code' => 'NTPC-KAH',
                'location' => 'Kahalgaon, Bihar',
                'is_active' => true,
            ],
            [
                'name' => 'NTPC Singrauli',
                'code' => 'NTPC-SIN',
                'location' => 'Singrauli, Madhya Pradesh',
                'is_active' => true,
            ],
            [
                'name' => 'JSW Steel Jharsuguda',
                'code' => 'JSW-JHR',
                'location' => 'Jharsuguda, Odisha',
                'is_active' => true,
            ],
        ];

        foreach ($plants as $plant) {
            PowerPlant::firstOrCreate(
                ['code' => $plant['code']],
                $plant
            );
        }
    }
}

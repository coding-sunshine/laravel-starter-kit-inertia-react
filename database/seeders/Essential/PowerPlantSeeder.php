<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Models\PowerPlant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PowerPlantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $powerPlants = [
            ['name' => 'PSPM', 'code' => 'PSPM', 'location' => 'Pakur', 'is_active' => true],
            ['name' => 'BTPC', 'code' => 'BTPC', 'location' => 'Bokaro', 'is_active' => true],
            ['name' => 'BTMT', 'code' => 'BTMT', 'location' => 'Bokaro', 'is_active' => true],
            ['name' => 'STPS', 'code' => 'STPS', 'location' => 'Sarna', 'is_active' => true],
            ['name' => 'KPPS', 'code' => 'KPPS', 'location' => 'Karra', 'is_active' => true],
        ];

        foreach ($powerPlants as $plant) {
            PowerPlant::updateOrCreate(['code' => $plant['code']], $plant);
        }
    }
}

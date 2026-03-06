<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Models\PowerPlant;
use App\Models\PowerplantSidingDistance;
use App\Models\Siding;
use Illuminate\Database\Seeder;

final class PowerplantSidingDistanceSeeder extends Seeder
{
    /**
     * Seeder dependencies (power plants and sidings must exist).
     *
     * @var array<string>
     */
    public array $dependencies = ['PowerPlantSeeder', 'SidingSeeder'];

    /**
     * Run the database seeds.
     * Seeds distance from a reference power plant (PSPM) to each siding so that
     * vehicle dispatch import can resolve siding from distance_km.
     */
    public function run(): void
    {
        $plant = PowerPlant::query()->where('code', 'PSPM')->first();
        if (! $plant) {
            $this->command?->warn('PSPM power plant not found. Run PowerPlantSeeder first.');

            return;
        }

        $distances = [
            [55.0, 'PKUR'],   // Pakur Siding
            [71.0, 'DUMK'],   // Dumka (Goods Shed)
            [73.05, 'KURWA'], // Kurwa Railway Siding
        ];

        foreach ($distances as [$distanceKm, $sidingCode]) {
            $siding = Siding::query()->where('code', $sidingCode)->first();
            if (! $siding) {
                $this->command?->warn("Siding with code {$sidingCode} not found. Run SidingSeeder first.");

                continue;
            }

            PowerplantSidingDistance::query()->updateOrCreate(
                [
                    'power_plant_id' => $plant->id,
                    'siding_id' => $siding->id,
                ],
                ['distance_km' => $distanceKm]
            );
        }
    }
}

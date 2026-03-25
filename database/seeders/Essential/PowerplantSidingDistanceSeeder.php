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
     */
    public function run(): void
    {
        $sidingCodes = ['PKUR', 'DUMK', 'KURWA'];
        $plantCodes = ['BTPC', 'BTMT', 'KPPS', 'PSPM', 'STPS'];

        $sidings = Siding::query()
            ->whereIn('code', $sidingCodes)
            ->get()
            ->keyBy('code');

        $plants = PowerPlant::query()
            ->whereIn('code', $plantCodes)
            ->get()
            ->keyBy('code');

        $missingSidings = array_values(array_diff($sidingCodes, $sidings->keys()->all()));
        $missingPlants = array_values(array_diff($plantCodes, $plants->keys()->all()));

        foreach ($missingSidings as $code) {
            $this->command?->warn("Siding with code {$code} not found. Run SidingSeeder first.");
        }

        foreach ($missingPlants as $code) {
            $this->command?->warn("Power plant with code {$code} not found. Run PowerPlantSeeder first.");
        }

        if ($missingSidings !== [] || $missingPlants !== []) {
            return;
        }

        $distanceMatrix = [
            'PKUR' => ['BTPC' => 130, 'BTMT' => 250, 'KPPS' => 300, 'PSPM' => 110, 'STPS' => 240],
            'DUMK' => ['BTPC' => 130, 'BTMT' => 280, 'KPPS' => 340, 'PSPM' => 120, 'STPS' => 250],
            'KURWA' => ['BTPC' => 125, 'BTMT' => 270, 'KPPS' => 330, 'PSPM' => 110, 'STPS' => 240],
        ];

        $now = now();
        $rows = [];

        foreach ($distanceMatrix as $sidingCode => $plantDistances) {
            $siding = $sidings->get($sidingCode);

            foreach ($plantDistances as $plantCode => $distanceKm) {
                $plant = $plants->get($plantCode);

                $rows[] = [
                    'power_plant_id' => $plant->id,
                    'siding_id' => $siding->id,
                    'distance_km' => $distanceKm,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        PowerplantSidingDistance::query()->upsert(
            $rows,
            ['power_plant_id', 'siding_id'],
            ['distance_km', 'updated_at']
        );
    }
}

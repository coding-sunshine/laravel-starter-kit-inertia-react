<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;

final class LoaderSeeder extends Seeder
{
    /**
     * Seeder dependencies.
     *
     * @var array<string>
     */
    public array $dependencies = ['SidingSeeder'];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $loaders = [
            [
                'siding' => 'PKR', // station_code
                'code' => 'LDR-16',
                'loader_name' => 'Loader 16 - Backhoe',
                'loader_type' => 'Backhoe',
                'make_model' => 'Caterpillar CAT 320',
                'last_calibration_date' => now()->subMonths(2),
            ],
            [
                'siding' => 'DMK',
                'code' => 'LDR-11',
                'loader_name' => 'Loader 11 - Excavator',
                'loader_type' => 'Excavator',
                'make_model' => 'Volvo EC210B',
                'last_calibration_date' => now()->subMonths(1),
            ],
            [
                'siding' => 'KRW',
                'code' => 'LDR-17',
                'loader_name' => 'Loader 17 - Wheel Loader',
                'loader_type' => 'Wheel Loader',
                'make_model' => 'Komatsu WA900',
                'last_calibration_date' => now()->subMonths(3),
            ],
        ];

        foreach ($loaders as $loader) {
            $stationCode = $loader['siding'];
            $siding = \App\Models\Siding::where('station_code', $stationCode)->first();
            if ($siding) {
                \App\Models\Loader::firstOrCreate(
                    ['code' => $loader['code']],
                    [
                        'siding_id' => $siding->id,
                        'loader_name' => $loader['loader_name'],
                        'loader_type' => $loader['loader_type'],
                        'make_model' => $loader['make_model'],
                        'last_calibration_date' => $loader['last_calibration_date'],
                    ]
                );
            }
        }
    }
}

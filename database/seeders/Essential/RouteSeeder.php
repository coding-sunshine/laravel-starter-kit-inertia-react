<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;

final class RouteSeeder extends Seeder
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
        $routes = [
            [
                'siding' => 'PKR', // station_code in sidings table
                'route_name' => 'Pakur to Rajpura Coal Terminal',
                'start_location' => 'Pakur Siding',
                'end_location' => 'Rajpura Coal Terminal',
                'expected_distance_km' => 45,
                'coordinates' => [[87.78, 24.45], [87.92, 24.52]],
            ],
            [
                'siding' => 'DMK',
                'route_name' => 'Dumka to NTPC Singrauli',
                'start_location' => 'Dumka Siding',
                'end_location' => 'NTPC Singrauli',
                'expected_distance_km' => 280,
                'coordinates' => [[87.25, 24.42], [82.67, 24.18]],
            ],
            [
                'siding' => 'KRW',
                'route_name' => 'Kurwa to JSW Steel Jharsuguda',
                'start_location' => 'Kurwa Siding',
                'end_location' => 'JSW Steel Jharsuguda',
                'expected_distance_km' => 190,
                'coordinates' => [[84.78, 22.05], [84.15, 21.85]],
            ],
        ];

        foreach ($routes as $route) {
            $stationCode = $route['siding'];
            $siding = \App\Models\Siding::where('station_code', $stationCode)->first();
            if ($siding) {
                \App\Models\Route::firstOrCreate(
                    ['route_name' => $route['route_name']],
                    [
                        'siding_id' => $siding->id,
                        'start_location' => $route['start_location'],
                        'end_location' => $route['end_location'],
                        'expected_distance_km' => $route['expected_distance_km'],
                        'geo_json_path_data' => json_encode([
                            'type' => 'LineString',
                            'coordinates' => $route['coordinates'],
                        ]),
                    ]
                );
            }
        }
    }
}

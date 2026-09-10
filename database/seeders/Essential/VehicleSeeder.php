<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;

/**
 * Seeds reference vehicles. DISABLED: contained dummy/sample vehicles only.
 * For production, register vehicles via the app. Re-enable and replace with
 * real data or move to Development if you need sample vehicles for demos.
 */
final class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command?->info('VehicleSeeder disabled (was dummy data only). Register vehicles via the app.');

        // Original dummy vehicles (HR26AB1234, Sharma Transport, etc.) – re-enable only for dev/demo.
        // $vehicles = [
        //     ['vehicle_number' => 'HR26AB1234', 'rfid_tag' => 'RFID-001', ...],
        //     ...
        // ];
        // foreach ($vehicles as $vehicle) {
        //     \App\Models\Vehicle::query()->firstOrCreate(['vehicle_number' => $vehicle['vehicle_number']], $vehicle);
        // }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;

final class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicles = [
            ['vehicle_number' => 'HR26AB1234', 'rfid_tag' => 'RFID-001', 'permitted_capacity_mt' => 30, 'tare_weight_mt' => 8, 'owner_name' => 'Sharma Transport', 'vehicle_type' => 'Truck', 'gps_device_id' => 'GPS-SIM-001'],
            ['vehicle_number' => 'HR26AB1235', 'rfid_tag' => 'RFID-002', 'permitted_capacity_mt' => 30, 'tare_weight_mt' => 8.2, 'owner_name' => 'Sharma Transport', 'vehicle_type' => 'Truck', 'gps_device_id' => 'GPS-SIM-002'],
            ['vehicle_number' => 'PB08AB5678', 'rfid_tag' => 'RFID-003', 'permitted_capacity_mt' => 32, 'tare_weight_mt' => 8.5, 'owner_name' => 'Poonam Logistics', 'vehicle_type' => 'Truck', 'gps_device_id' => 'GPS-SIM-003'],
            ['vehicle_number' => 'DL01AB9999', 'rfid_tag' => 'RFID-004', 'permitted_capacity_mt' => 28, 'tare_weight_mt' => 7.8, 'owner_name' => 'Capital Freights', 'vehicle_type' => 'Truck', 'gps_device_id' => 'GPS-SIM-004'],
            ['vehicle_number' => 'RJ14AB2020', 'rfid_tag' => 'RFID-005', 'permitted_capacity_mt' => 30, 'tare_weight_mt' => 8.1, 'owner_name' => 'Rajasthan Haulage', 'vehicle_type' => 'Truck', 'gps_device_id' => 'GPS-SIM-005'],
        ];

        foreach ($vehicles as $vehicle) {
            \App\Models\Vehicle::firstOrCreate(
                ['vehicle_number' => $vehicle['vehicle_number']],
                $vehicle
            );
        }
    }
}

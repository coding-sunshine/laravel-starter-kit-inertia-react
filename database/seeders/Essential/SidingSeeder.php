<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;

final class SidingSeeder extends Seeder
{
    /**
     * Run the database seeds for Railway Sidings.
     * Creates the three main coal logistics sidings: Pakur, Dumka, Kurwa (Jharkhand)
     */
    public function run(): void
    {
        // Get or create the default organization (owner_id set by UserSeeder)
        $org = \App\Models\Organization::query()->firstOrCreate(['slug' => 'default'], [
            'name' => 'Railway Rake Management',
            'billing_email' => 'billing@rrmcs.local',
            'owner_id' => null,
        ]);

        // Create the three main sidings
        $sidings = [
            [
                'name' => 'Pakur Siding',
                'code' => 'PKUR',
                'location' => 'Pakur, Jharkhand',
                'station_code' => 'PKR',
                'is_active' => true,
            ],
            [
                'name' => 'Dumka Siding',
                'code' => 'DUMK',
                'location' => 'Dumka, Jharkhand',
                'station_code' => 'DMK',
                'is_active' => true,
            ],
            [
                'name' => 'Kurwa Siding',
                'code' => 'KURWA',
                'location' => 'Kurwa, Jharkhand',
                'station_code' => 'KRW',
                'is_active' => true,
            ],
        ];

        foreach ($sidings as $sidingData) {
            \App\Models\Siding::query()->firstOrCreate(['code' => $sidingData['code']], array_merge($sidingData, ['organization_id' => $org->id]));
        }
    }
}

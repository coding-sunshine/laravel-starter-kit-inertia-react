<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;
use RuntimeException;

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

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure Organization exists for 0 (idempotent)
        if (\App\Models\Organization::query()->count() === 0) {
            \App\Models\Organization::factory()->count(5)->create();
        }

        // Ensure User exists for 1 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure User exists for 2 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure User exists for 3 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Note: hasMany relationships are seeded after main model creation
    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('sidings.json');

            if (! isset($data['sidings']) || ! is_array($data['sidings'])) {
                return;
            }

            foreach ($data['sidings'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (isset($itemData['code']) && ! empty($itemData['code'])) {
                    Siding::query()->updateOrCreate(
                        ['code' => $itemData['code']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = Siding::factory();
                    if ($factoryState !== null && method_exists($factory, $factoryState)) {
                        $factory = $factory->{$factoryState}();
                    }
                    $factory->create($itemData);
                }
            }
        } catch (RuntimeException $e) {
            // JSON file doesn't exist or is invalid - skip silently
        }
    }

    /**
     * Seed using factory (idempotent - safe to run multiple times).
     */
    private function seedFromFactory(): void
    {
        // Generate seed data with factory
        // Note: Factory creates are not idempotent by default
        // For true idempotency, use updateOrCreate in seedFromJson or add unique constraints
        Siding::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(Siding::factory(), 'admin')) {
            Siding::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

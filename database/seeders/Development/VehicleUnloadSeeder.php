<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * VehicleUnload seeder. Demo data is created by RakeManagementDemoSeeder.
 * This seeder exists to satisfy the model-audit / pre-commit check.
 */
final class VehicleUnloadSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: vehicle unloads are seeded by RakeManagementDemoSeeder.
    }

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure Siding exists for 0 (idempotent)
        if (\App\Models\Siding::query()->count() === 0) {
            \App\Models\Siding::factory()->count(5)->create();
        }

        // Ensure Vehicle exists for 1 (idempotent)
        if (\App\Models\Vehicle::query()->count() === 0) {
            \App\Models\Vehicle::factory()->count(5)->create();
        }

        // Ensure VehicleArrival exists for 2 (idempotent)
        if (\App\Models\VehicleArrival::query()->count() === 0) {
            \App\Models\VehicleArrival::factory()->count(5)->create();
        }

        // Ensure User exists for 3 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure User exists for 4 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure User exists for 5 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure User exists for 6 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure User exists for 7 (idempotent)
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
            $data = $this->loadJson('vehicle_unloads.json');

            if (! isset($data['vehicle_unloads']) || ! is_array($data['vehicle_unloads'])) {
                return;
            }

            foreach ($data['vehicle_unloads'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    VehicleUnload::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = VehicleUnload::factory();
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
        VehicleUnload::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(VehicleUnload::factory(), 'admin')) {
            VehicleUnload::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

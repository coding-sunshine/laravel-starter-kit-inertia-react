<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\PropertyReservation;
use Illuminate\Database\Seeder;
use RuntimeException;

final class PropertyReservationSeeder extends Seeder
{
    public function run(): void
    {
        if (PropertyReservation::query()->exists()) {
            return;
        }

        PropertyReservation::factory()->count(10)->create();
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

        // Ensure Contact exists for 1 (idempotent)
        if (\App\Models\Contact::query()->count() === 0) {
            \App\Models\Contact::factory()->count(5)->create();
        }

        // Ensure Contact exists for 2 (idempotent)
        if (\App\Models\Contact::query()->count() === 0) {
            \App\Models\Contact::factory()->count(5)->create();
        }

        // Ensure Contact exists for 3 (idempotent)
        if (\App\Models\Contact::query()->count() === 0) {
            \App\Models\Contact::factory()->count(5)->create();
        }

        // Ensure User exists for 4 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure Lot exists for 5 (idempotent)
        if (\App\Models\Lot::query()->count() === 0) {
            \App\Models\Lot::factory()->count(5)->create();
        }

        // Ensure Project exists for 6 (idempotent)
        if (\App\Models\Project::query()->count() === 0) {
            \App\Models\Project::factory()->count(5)->create();
        }

    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('property_reservations.json');

            if (! isset($data['property_reservations']) || ! is_array($data['property_reservations'])) {
                return;
            }

            foreach ($data['property_reservations'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    PropertyReservation::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = PropertyReservation::factory();
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
        PropertyReservation::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(PropertyReservation::factory(), 'admin')) {
            PropertyReservation::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

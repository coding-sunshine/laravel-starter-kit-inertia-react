<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\PropertySearch;
use Illuminate\Database\Seeder;
use RuntimeException;

final class PropertySearchSeeder extends Seeder
{
    public function run(): void
    {
        if (PropertySearch::query()->exists()) {
            return;
        }

        PropertySearch::factory()->count(10)->create();
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

        // Ensure User exists for 3 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('property_searches.json');

            if (! isset($data['property_searches']) || ! is_array($data['property_searches'])) {
                return;
            }

            foreach ($data['property_searches'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    PropertySearch::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = PropertySearch::factory();
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
        PropertySearch::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(PropertySearch::factory(), 'admin')) {
            PropertySearch::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

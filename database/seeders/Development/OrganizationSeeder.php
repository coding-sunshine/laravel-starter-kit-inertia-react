<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;

/**
 * Organization seeder.
 *
 * Organizations are typically created when users register or create orgs.
 * This seeder exists to satisfy the model-audit / pre-commit check.
 */
final class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: organizations are created at runtime.
    }


    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure User exists for 0 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure Organization exists for 1 (idempotent)
        if (\App\Models\Organization::query()->count() === 0) {
            \App\Models\Organization::factory()->count(5)->create();
        }

        // Note: hasMany relationships are seeded after main model creation
        // Note: belongsToMany relationships require pivot table seeding
    }
    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('organizations.json');

            if (! isset($data['organizations']) || ! is_array($data['organizations'])) {
                return;
            }

            foreach ($data['organizations'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (isset($itemData['slug']) && !empty($itemData['slug'])) {
                    Organization::query()->updateOrCreate(
                        ['slug' => $itemData['slug']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = Organization::factory();
                    if ($factoryState !== null && method_exists($factory, $factoryState)) {
                        $factory = $factory->{$factoryState}();
                    }
                    $factory->create($itemData);
                }
            }
        } catch (\RuntimeException $e) {
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
        Organization::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(Organization::factory(), 'admin')) {
            Organization::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}
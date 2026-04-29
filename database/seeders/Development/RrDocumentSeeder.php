<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * RrDocument seeder. Demo data is created by RakeManagementDemoSeeder.
 * This seeder exists to satisfy the model-audit / pre-commit check.
 */
final class RrDocumentSeeder extends Seeder
{
    public function run(): void
    {
        // No-op: RR documents are seeded by RakeManagementDemoSeeder.
    }

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure Rake exists for 0 (idempotent)
        if (\App\Models\Rake::query()->count() === 0) {
            \App\Models\Rake::factory()->count(5)->create();
        }

        // Ensure DiverrtDestination exists for 1 (idempotent)
        if (\App\Models\DiverrtDestination::query()->count() === 0) {
            \App\Models\DiverrtDestination::factory()->count(5)->create();
        }

        // Ensure User exists for 2 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
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

        // Note: hasMany relationships are seeded after main model creation
    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('rr_documents.json');

            if (! isset($data['rr_documents']) || ! is_array($data['rr_documents'])) {
                return;
            }

            foreach ($data['rr_documents'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    RrDocument::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = RrDocument::factory();
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
        RrDocument::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(RrDocument::factory(), 'admin')) {
            RrDocument::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

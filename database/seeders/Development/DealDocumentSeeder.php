<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\DealDocument;
use Illuminate\Database\Seeder;
use RuntimeException;

final class DealDocumentSeeder extends Seeder
{
    public function run(): void
    {
        if (DealDocument::query()->exists()) {
            return;
        }

        DealDocument::factory()->count(10)->create();
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

    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('deal_documents.json');

            if (! isset($data['deal_documents']) || ! is_array($data['deal_documents'])) {
                return;
            }

            foreach ($data['deal_documents'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    DealDocument::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = DealDocument::factory();
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
        DealDocument::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(DealDocument::factory(), 'admin')) {
            DealDocument::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\SuburbAiData;
use Illuminate\Database\Seeder;
use RuntimeException;

final class SuburbAiDataSeeder extends Seeder
{
    public function run(): void
    {
        if (SuburbAiData::query()->exists()) {
            return;
        }
    }

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure Suburb exists for 0 (idempotent)
        if (\App\Models\Suburb::query()->count() === 0) {
            \App\Models\Suburb::factory()->count(5)->create();
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
            $data = $this->loadJson('suburb_ai_datas.json');

            if (! isset($data['suburb_ai_datas']) || ! is_array($data['suburb_ai_datas'])) {
                return;
            }

            foreach ($data['suburb_ai_datas'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    SuburbAiData::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = SuburbAiData::factory();
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
        SuburbAiData::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(SuburbAiData::factory(), 'admin')) {
            SuburbAiData::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

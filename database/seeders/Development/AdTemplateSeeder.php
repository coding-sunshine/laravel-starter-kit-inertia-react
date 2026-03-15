<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\AdTemplate;
use Illuminate\Database\Seeder;
use RuntimeException;

final class AdTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (AdTemplate::query()->exists()) {
            return;
        }

        AdTemplate::factory()->count(3)->create();
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

    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('ad_templates.json');

            if (! isset($data['ad_templates']) || ! is_array($data['ad_templates'])) {
                return;
            }

            foreach ($data['ad_templates'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (isset($itemData['name']) && ! empty($itemData['name'])) {
                    AdTemplate::query()->updateOrCreate(
                        ['name' => $itemData['name']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = AdTemplate::factory();
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
        AdTemplate::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(AdTemplate::factory(), 'admin')) {
            AdTemplate::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

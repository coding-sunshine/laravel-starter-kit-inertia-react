<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\RetargetingPixel;
use Illuminate\Database\Seeder;
use RuntimeException;

final class RetargetingPixelSeeder extends Seeder
{
    public function run(): void
    {
        if (RetargetingPixel::query()->exists()) {
            return;
        }

        RetargetingPixel::factory()->count(3)->create();
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
            $data = $this->loadJson('retargeting_pixels.json');

            if (! isset($data['retargeting_pixels']) || ! is_array($data['retargeting_pixels'])) {
                return;
            }

            foreach ($data['retargeting_pixels'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (isset($itemData['name']) && ! empty($itemData['name'])) {
                    RetargetingPixel::query()->updateOrCreate(
                        ['name' => $itemData['name']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = RetargetingPixel::factory();
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
        RetargetingPixel::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(RetargetingPixel::factory(), 'admin')) {
            RetargetingPixel::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

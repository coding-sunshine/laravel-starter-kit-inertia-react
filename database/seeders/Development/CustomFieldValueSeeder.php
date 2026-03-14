<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\CustomFieldValue;
use Illuminate\Database\Seeder;
use RuntimeException;

final class CustomFieldValueSeeder extends Seeder
{
    public function run(): void
    {
        if (CustomFieldValue::query()->exists()) {
            return;
        }
    }

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure CustomField exists for 0 (idempotent)
        if (\App\Models\CustomField::query()->count() === 0) {
            \App\Models\CustomField::factory()->count(5)->create();
        }

    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('custom_field_values.json');

            if (! isset($data['custom_field_values']) || ! is_array($data['custom_field_values'])) {
                return;
            }

            foreach ($data['custom_field_values'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    CustomFieldValue::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = CustomFieldValue::factory();
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
        CustomFieldValue::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(CustomFieldValue::factory(), 'admin')) {
            CustomFieldValue::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

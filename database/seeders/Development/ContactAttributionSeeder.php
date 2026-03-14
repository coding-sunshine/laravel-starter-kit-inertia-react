<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\ContactAttribution;
use Illuminate\Database\Seeder;
use RuntimeException;

final class ContactAttributionSeeder extends Seeder
{
    public function run(): void
    {
        if (ContactAttribution::query()->exists()) {
            return;
        }
    }

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure Contact exists for 0 (idempotent)
        if (\App\Models\Contact::query()->count() === 0) {
            \App\Models\Contact::factory()->count(5)->create();
        }

        // Ensure Contact exists for 1 (idempotent)
        if (\App\Models\Contact::query()->count() === 0) {
            \App\Models\Contact::factory()->count(5)->create();
        }

    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('contact_attributions.json');

            if (! isset($data['contact_attributions']) || ! is_array($data['contact_attributions'])) {
                return;
            }

            foreach ($data['contact_attributions'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    ContactAttribution::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = ContactAttribution::factory();
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
        ContactAttribution::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(ContactAttribution::factory(), 'admin')) {
            ContactAttribution::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

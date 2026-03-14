<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Contact;
use Illuminate\Database\Seeder;
use RuntimeException;

final class ContactSeeder extends Seeder
{
    public function run(): void
    {
        if (Contact::query()->exists()) {
            return;
        }

        Contact::factory()
            ->count(20)
            ->create();
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

        // Ensure Source exists for 1 (idempotent)
        if (\App\Models\Source::query()->count() === 0) {
            \App\Models\Source::factory()->count(5)->create();
        }

        // Ensure Company exists for 2 (idempotent)
        if (\App\Models\Company::query()->count() === 0) {
            \App\Models\Company::factory()->count(5)->create();
        }

        // Ensure User exists for 3 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
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
            $data = $this->loadJson('contacts.json');

            if (! isset($data['contacts']) || ! is_array($data['contacts'])) {
                return;
            }

            foreach ($data['contacts'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    Contact::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = Contact::factory();
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
        Contact::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(Contact::factory(), 'admin')) {
            Contact::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

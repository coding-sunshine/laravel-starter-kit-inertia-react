<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\ListingVersion;
use Illuminate\Database\Seeder;
use RuntimeException;

final class ListingVersionSeeder extends Seeder
{
    public function run(): void
    {
        if (ListingVersion::query()->exists()) {
            return;
        }
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

    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('listing_versions.json');

            if (! isset($data['listing_versions']) || ! is_array($data['listing_versions'])) {
                return;
            }

            foreach ($data['listing_versions'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    ListingVersion::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = ListingVersion::factory();
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
        ListingVersion::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(ListingVersion::factory(), 'admin')) {
            ListingVersion::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

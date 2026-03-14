<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\WordpressWebsite;
use Illuminate\Database\Seeder;
use RuntimeException;

final class WordpressWebsiteSeeder extends Seeder
{
    public function run(): void
    {
        if (WordpressWebsite::query()->exists()) {
            return;
        }
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
            $data = $this->loadJson('wordpress_websites.json');

            if (! isset($data['wordpress_websites']) || ! is_array($data['wordpress_websites'])) {
                return;
            }

            foreach ($data['wordpress_websites'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    WordpressWebsite::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = WordpressWebsite::factory();
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
        WordpressWebsite::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(WordpressWebsite::factory(), 'admin')) {
            WordpressWebsite::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

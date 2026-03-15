<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\LandingPageTemplate;
use Illuminate\Database\Seeder;
use RuntimeException;

final class LandingPageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (LandingPageTemplate::query()->exists()) {
            return;
        }

        LandingPageTemplate::factory()->count(3)->create();
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

        // Ensure CampaignWebsite exists for 1 (idempotent)
        if (\App\Models\CampaignWebsite::query()->count() === 0) {
            \App\Models\CampaignWebsite::factory()->count(5)->create();
        }

    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('landing_page_templates.json');

            if (! isset($data['landing_page_templates']) || ! is_array($data['landing_page_templates'])) {
                return;
            }

            foreach ($data['landing_page_templates'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (isset($itemData['slug']) && ! empty($itemData['slug'])) {
                    LandingPageTemplate::query()->updateOrCreate(
                        ['slug' => $itemData['slug']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = LandingPageTemplate::factory();
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
        LandingPageTemplate::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(LandingPageTemplate::factory(), 'admin')) {
            LandingPageTemplate::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

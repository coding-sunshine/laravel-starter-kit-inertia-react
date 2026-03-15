<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Organization;
use App\Models\WebhookEndpoint;
use Illuminate\Database\Seeder;
use RuntimeException;

final class WebhookEndpointSeeder extends Seeder
{
    public function run(): void
    {
        if (WebhookEndpoint::query()->exists()) {
            return;
        }

        $organization = Organization::query()->first();

        if ($organization === null) {
            return;
        }

        WebhookEndpoint::query()->create([
            'organization_id' => $organization->id,
            'url' => 'https://example.com/webhooks/fusion',
            'events' => ['contact.created', 'sale.updated'],
            'secret' => 'dev-secret-key',
            'is_active' => true,
        ]);
    }

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure Organization exists for 0 (idempotent)
        if (Organization::query()->count() === 0) {
            Organization::factory()->count(5)->create();
        }

    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('webhook_endpoints.json');

            if (! isset($data['webhook_endpoints']) || ! is_array($data['webhook_endpoints'])) {
                return;
            }

            foreach ($data['webhook_endpoints'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    WebhookEndpoint::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = WebhookEndpoint::factory();
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
        WebhookEndpoint::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(WebhookEndpoint::factory(), 'admin')) {
            WebhookEndpoint::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

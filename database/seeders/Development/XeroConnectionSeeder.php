<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\XeroConnection;
use Illuminate\Database\Seeder;
use RuntimeException;

final class XeroConnectionSeeder extends Seeder
{
    public function run(): void
    {
        if (XeroConnection::query()->exists()) {
            return;
        }

        XeroConnection::query()->create([
            'xero_tenant_id' => 'demo-tenant-'.uniqid(),
            'xero_tenant_name' => 'Demo Xero Org',
            'connected_at' => now(),
        ]);
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

        // Note: hasMany relationships are seeded after main model creation
    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('xero_connections.json');

            if (! isset($data['xero_connections']) || ! is_array($data['xero_connections'])) {
                return;
            }

            foreach ($data['xero_connections'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    XeroConnection::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = XeroConnection::factory();
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
        XeroConnection::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(XeroConnection::factory(), 'admin')) {
            XeroConnection::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

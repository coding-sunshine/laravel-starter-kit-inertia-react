<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\AiCreditPool;
use Illuminate\Database\Seeder;
use RuntimeException;

final class AiCreditPoolSeeder extends Seeder
{
    public function run(): void
    {
        if (AiCreditPool::exists()) {
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
            $data = $this->loadJson('ai_credit_pools.json');

            if (! isset($data['ai_credit_pools']) || ! is_array($data['ai_credit_pools'])) {
                return;
            }

            foreach ($data['ai_credit_pools'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    AiCreditPool::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = AiCreditPool::factory();
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
        AiCreditPool::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(AiCreditPool::factory(), 'admin')) {
            AiCreditPool::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

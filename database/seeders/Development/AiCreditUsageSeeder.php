<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\AiCreditUsage;
use Illuminate\Database\Seeder;
use RuntimeException;

final class AiCreditUsageSeeder extends Seeder
{
    public function run(): void
    {
        if (AiCreditUsage::exists()) {
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

        // Ensure User exists for 1 (idempotent)
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
            $data = $this->loadJson('ai_credit_usages.json');

            if (! isset($data['ai_credit_usages']) || ! is_array($data['ai_credit_usages'])) {
                return;
            }

            foreach ($data['ai_credit_usages'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    AiCreditUsage::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = AiCreditUsage::factory();
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
        AiCreditUsage::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(AiCreditUsage::factory(), 'admin')) {
            AiCreditUsage::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\PaymentStage;
use Illuminate\Database\Seeder;
use RuntimeException;

final class PaymentStageSeeder extends Seeder
{
    public function run(): void
    {
        if (PaymentStage::query()->exists()) {
            return;
        }

        PaymentStage::factory()->count(10)->create();
    }

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure Sale exists for 0 (idempotent)
        if (\App\Models\Sale::query()->count() === 0) {
            \App\Models\Sale::factory()->count(5)->create();
        }

        // Ensure Organization exists for 1 (idempotent)
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
            $data = $this->loadJson('payment_stages.json');

            if (! isset($data['payment_stages']) || ! is_array($data['payment_stages'])) {
                return;
            }

            foreach ($data['payment_stages'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    PaymentStage::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = PaymentStage::factory();
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
        PaymentStage::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(PaymentStage::factory(), 'admin')) {
            PaymentStage::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

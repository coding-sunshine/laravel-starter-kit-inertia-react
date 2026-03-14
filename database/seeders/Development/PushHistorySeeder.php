<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\PushHistory;
use Illuminate\Database\Seeder;
use RuntimeException;

final class PushHistorySeeder extends Seeder
{
    public function run(): void
    {
        if (PushHistory::query()->exists()) {
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
            $data = $this->loadJson('push_histories.json');

            if (! isset($data['push_histories']) || ! is_array($data['push_histories'])) {
                return;
            }

            foreach ($data['push_histories'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    PushHistory::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = PushHistory::factory();
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
        PushHistory::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(PushHistory::factory(), 'admin')) {
            PushHistory::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

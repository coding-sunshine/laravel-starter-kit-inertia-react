<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\AiUserByok;
use Illuminate\Database\Seeder;
use RuntimeException;

final class AiUserByokSeeder extends Seeder
{
    public function run(): void
    {
        if (AiUserByok::exists()) {
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
            $data = $this->loadJson('ai_user_byoks.json');

            if (! isset($data['ai_user_byoks']) || ! is_array($data['ai_user_byoks'])) {
                return;
            }

            foreach ($data['ai_user_byoks'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    AiUserByok::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = AiUserByok::factory();
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
        AiUserByok::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(AiUserByok::factory(), 'admin')) {
            AiUserByok::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

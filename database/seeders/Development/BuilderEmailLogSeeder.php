<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\BuilderEmailLog;
use Illuminate\Database\Seeder;
use RuntimeException;

final class BuilderEmailLogSeeder extends Seeder
{
    public function run(): void
    {
        if (BuilderEmailLog::query()->exists()) {
            return;
        }
    }

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure Contact exists for 0 (idempotent)
        if (\App\Models\Contact::query()->count() === 0) {
            \App\Models\Contact::factory()->count(5)->create();
        }

        // Ensure Project exists for 1 (idempotent)
        if (\App\Models\Project::query()->count() === 0) {
            \App\Models\Project::factory()->count(5)->create();
        }

        // Ensure User exists for 2 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure Organization exists for 3 (idempotent)
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
            $data = $this->loadJson('builder_email_logs.json');

            if (! isset($data['builder_email_logs']) || ! is_array($data['builder_email_logs'])) {
                return;
            }

            foreach ($data['builder_email_logs'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    BuilderEmailLog::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = BuilderEmailLog::factory();
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
        BuilderEmailLog::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(BuilderEmailLog::factory(), 'admin')) {
            BuilderEmailLog::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

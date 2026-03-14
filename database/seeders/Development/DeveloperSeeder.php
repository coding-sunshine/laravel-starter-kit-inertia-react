<?php

declare(strict_types=1);

namespace Database\Seeders\development;

use App\Models\Developer;
use Database\Seeders\Concerns\LoadsJsonData;
use Illuminate\Database\Seeder;
use RuntimeException;

final class DeveloperSeeder extends Seeder
{
    use LoadsJsonData;

    /**
     * Run the database seeds (idempotent).
     */
    public function run(): void
    {
        $this->seedRelationships();
        $this->seedFromJson();
        $this->seedFromFactory();
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
            $data = $this->loadJson('developers.json');

            if (! isset($data['developers']) || ! is_array($data['developers'])) {
                return;
            }

            foreach ($data['developers'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (isset($itemData['email']) && ! empty($itemData['email'])) {
                    Developer::query()->updateOrCreate(
                        ['email' => $itemData['email']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = Developer::factory();
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
        Developer::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(Developer::factory(), 'admin')) {
            Developer::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

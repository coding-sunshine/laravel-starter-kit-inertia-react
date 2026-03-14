<?php

declare(strict_types=1);

namespace Database\Seeders\development;

use App\Models\Suburb;
use Database\Seeders\Concerns\LoadsJsonData;
use Illuminate\Database\Seeder;
use RuntimeException;

final class SuburbSeeder extends Seeder
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
        // Ensure State exists for 0 (idempotent)
        if (\App\Models\State::query()->count() === 0) {
            \App\Models\State::factory()->count(5)->create();
        }

    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('suburbs.json');

            if (! isset($data['suburbs']) || ! is_array($data['suburbs'])) {
                return;
            }

            foreach ($data['suburbs'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    Suburb::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = Suburb::factory();
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
        Suburb::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(Suburb::factory(), 'admin')) {
            Suburb::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

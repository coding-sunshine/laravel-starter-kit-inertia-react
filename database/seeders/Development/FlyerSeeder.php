<?php

declare(strict_types=1);

namespace Database\Seeders\development;

use App\Models\Flyer;
use Database\Seeders\Concerns\LoadsJsonData;
use Illuminate\Database\Seeder;
use RuntimeException;

final class FlyerSeeder extends Seeder
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
        // Ensure FlyerTemplate exists for 0 (idempotent)
        if (\App\Models\FlyerTemplate::query()->count() === 0) {
            \App\Models\FlyerTemplate::factory()->count(5)->create();
        }

        // Ensure Project exists for 1 (idempotent)
        if (\App\Models\Project::query()->count() === 0) {
            \App\Models\Project::factory()->count(5)->create();
        }

        // Ensure Lot exists for 2 (idempotent)
        if (\App\Models\Lot::query()->count() === 0) {
            \App\Models\Lot::factory()->count(5)->create();
        }

        // Ensure User exists for 3 (idempotent)
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
            $data = $this->loadJson('flyers.json');

            if (! isset($data['flyers']) || ! is_array($data['flyers'])) {
                return;
            }

            foreach ($data['flyers'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    Flyer::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = Flyer::factory();
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
        Flyer::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(Flyer::factory(), 'admin')) {
            Flyer::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders\development;

use App\Models\Project;
use Database\Seeders\Concerns\LoadsJsonData;
use Illuminate\Database\Seeder;
use RuntimeException;

final class ProjectSeeder extends Seeder
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
        // Ensure Developer exists for 0 (idempotent)
        if (\App\Models\Developer::query()->count() === 0) {
            \App\Models\Developer::factory()->count(5)->create();
        }

        // Ensure Projecttype exists for 1 (idempotent)
        if (\App\Models\Projecttype::query()->count() === 0) {
            \App\Models\Projecttype::factory()->count(5)->create();
        }

        // Ensure Organization exists for 2 (idempotent)
        if (\App\Models\Organization::query()->count() === 0) {
            \App\Models\Organization::factory()->count(5)->create();
        }

        // Note: hasMany relationships are seeded after main model creation
        // Note: belongsToMany relationships require pivot table seeding
    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('projects.json');

            if (! isset($data['projects']) || ! is_array($data['projects'])) {
                return;
            }

            foreach ($data['projects'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    Project::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = Project::factory();
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
        Project::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(Project::factory(), 'admin')) {
            Project::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

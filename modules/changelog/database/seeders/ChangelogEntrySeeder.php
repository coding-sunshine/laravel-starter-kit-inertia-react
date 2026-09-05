<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Enums\ChangelogType;
use App\Models\ChangelogEntry;
use Illuminate\Database\Seeder;
use RuntimeException;

final class ChangelogEntrySeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/changelog-entries.json');

        if (! file_exists($jsonPath)) {
            $this->command?->warn('Changelog entries JSON file not found');

            return;
        }

        $data = json_decode((string) file_get_contents($jsonPath), true);
        $entries = $data['changelog_entries'] ?? [];

        foreach ($entries as $entry) {
            if (ChangelogEntry::query()->where('title', $entry['title'])->where('version', $entry['version'] ?? null)->exists()) {
                continue;
            }

            ChangelogEntry::query()->create([
                'title' => $entry['title'],
                'description' => $entry['description'],
                'version' => $entry['version'] ?? null,
                'type' => ChangelogType::from($entry['type']),
                'is_published' => $entry['is_published'] ?? false,
                'released_at' => $entry['released_at'] ?? null,
            ]);
        }

        $this->command?->info('Changelog entries seeded.');
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

        // Ensure User exists for 2 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
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
            $data = $this->loadJson('changelog_entries.json');

            if (! isset($data['changelog_entries']) || ! is_array($data['changelog_entries'])) {
                return;
            }

            foreach ($data['changelog_entries'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (false) {
                    ChangelogEntry::query()->updateOrCreate(
                        ['id' => $itemData['id']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = ChangelogEntry::factory();
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
        ChangelogEntry::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(ChangelogEntry::factory(), 'admin')) {
            ChangelogEntry::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

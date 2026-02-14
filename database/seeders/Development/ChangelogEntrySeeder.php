<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Enums\ChangelogType;
use App\Models\ChangelogEntry;
use Illuminate\Database\Seeder;

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
}

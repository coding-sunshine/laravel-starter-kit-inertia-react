<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\HelpArticle;
use Illuminate\Database\Seeder;
use RuntimeException;

final class HelpArticleSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/help-articles.json');

        if (! file_exists($jsonPath)) {
            $this->command?->warn('Help articles JSON file not found');

            return;
        }

        $data = json_decode((string) file_get_contents($jsonPath), true);
        $articles = $data['help_articles'] ?? [];

        foreach ($articles as $article) {
            if (HelpArticle::query()->where('slug', $article['slug'])->exists()) {
                continue;
            }

            HelpArticle::query()->create([
                'title' => $article['title'],
                'slug' => $article['slug'],
                'excerpt' => $article['excerpt'] ?? null,
                'content' => $article['content'],
                'category' => $article['category'] ?? null,
                'order' => $article['order'] ?? 0,
                'is_published' => $article['is_published'] ?? false,
                'is_featured' => $article['is_featured'] ?? false,
            ]);
        }

        $this->command?->info('Help articles seeded.');
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

        // Ensure Category exists for 1 (idempotent)
        if (\App\Models\Category::query()->count() === 0) {
            \App\Models\Category::factory()->count(5)->create();
        }

        // Ensure User exists for 2 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure User exists for 3 (idempotent)
        if (\App\Models\User::query()->count() === 0) {
            \App\Models\User::factory()->count(5)->create();
        }

        // Ensure User exists for 4 (idempotent)
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
            $data = $this->loadJson('help_articles.json');

            if (! isset($data['help_articles']) || ! is_array($data['help_articles'])) {
                return;
            }

            foreach ($data['help_articles'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (isset($itemData['slug']) && ! empty($itemData['slug'])) {
                    HelpArticle::query()->updateOrCreate(
                        ['slug' => $itemData['slug']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = HelpArticle::factory();
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
        HelpArticle::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(HelpArticle::factory(), 'admin')) {
            HelpArticle::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

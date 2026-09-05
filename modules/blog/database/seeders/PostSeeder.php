<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

final class PostSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/posts.json');

        if (! file_exists($jsonPath)) {
            $this->command?->warn('Blog posts JSON file not found');

            return;
        }

        $data = json_decode((string) file_get_contents($jsonPath), true);
        $posts = $data['posts'] ?? [];

        foreach ($posts as $postData) {
            $author = User::query()->where('email', $postData['author_email'])->first();

            if (! $author) {
                $this->command?->warn("Author not found: {$postData['author_email']}");

                continue;
            }

            if (Post::query()->where('slug', $postData['slug'])->exists()) {
                continue;
            }

            Post::query()->create([
                'author_id' => $author->id,
                'title' => $postData['title'],
                'slug' => $postData['slug'],
                'excerpt' => $postData['excerpt'] ?? null,
                'content' => $postData['content'],
                'is_published' => $postData['is_published'] ?? false,
                'published_at' => $postData['published_at'] ?? null,
                'meta_title' => $postData['meta_title'] ?? null,
                'meta_description' => $postData['meta_description'] ?? null,
                'meta_keywords' => $postData['meta_keywords'] ?? null,
                'views' => $postData['views'] ?? 0,
            ]);
        }

        $this->command?->info('Blog posts seeded.');
    }

    /**
     * Seed relationships (idempotent).
     */
    private function seedRelationships(): void
    {
        // Ensure User exists for 0 (idempotent)
        if (User::query()->count() === 0) {
            User::factory()->count(5)->create();
        }

        // Ensure Organization exists for 1 (idempotent)
        if (\App\Models\Organization::query()->count() === 0) {
            \App\Models\Organization::factory()->count(5)->create();
        }

        // Ensure Category exists for 2 (idempotent)
        if (\App\Models\Category::query()->count() === 0) {
            \App\Models\Category::factory()->count(5)->create();
        }

        // Ensure User exists for 3 (idempotent)
        if (User::query()->count() === 0) {
            User::factory()->count(5)->create();
        }

        // Ensure User exists for 4 (idempotent)
        if (User::query()->count() === 0) {
            User::factory()->count(5)->create();
        }

        // Ensure User exists for 5 (idempotent)
        if (User::query()->count() === 0) {
            User::factory()->count(5)->create();
        }

    }

    /**
     * Seed from JSON data file (idempotent).
     */
    private function seedFromJson(): void
    {
        try {
            $data = $this->loadJson('posts.json');

            if (! isset($data['posts']) || ! is_array($data['posts'])) {
                return;
            }

            foreach ($data['posts'] as $itemData) {
                $factoryState = $itemData['_factory_state'] ?? null;
                unset($itemData['_factory_state']);

                // Use idempotent updateOrCreate if unique field exists
                if (isset($itemData['slug']) && ! empty($itemData['slug'])) {
                    Post::query()->updateOrCreate(
                        ['slug' => $itemData['slug']],
                        $itemData
                    );
                } else {
                    // Fallback to factory if no unique field
                    $factory = Post::factory();
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
        Post::factory()
            ->count(5)
            ->create();

        // Create admin users if applicable
        if (method_exists(Post::factory(), 'admin')) {
            Post::factory()
                ->admin()
                ->count(2)
                ->create();
        }
    }
}

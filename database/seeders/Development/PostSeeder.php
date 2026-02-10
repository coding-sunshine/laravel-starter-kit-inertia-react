<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

final class PostSeeder extends Seeder
{
    /**
     * @var array<string>
     */
    private array $dependencies = ['UsersSeeder'];

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

            Post::create([
                'author_id' => $author->id,
                'title' => $postData['title'],
                'slug' => $postData['slug'],
                'excerpt' => $postData['excerpt'] ?? null,
                'content' => $postData['content'],
                'is_published' => $postData['is_published'] ?? false,
                'published_at' => isset($postData['published_at']) ? $postData['published_at'] : null,
                'meta_title' => $postData['meta_title'] ?? null,
                'meta_description' => $postData['meta_description'] ?? null,
                'meta_keywords' => $postData['meta_keywords'] ?? null,
                'views' => $postData['views'] ?? 0,
            ]);
        }

        $this->command?->info('Blog posts seeded.');
    }
}

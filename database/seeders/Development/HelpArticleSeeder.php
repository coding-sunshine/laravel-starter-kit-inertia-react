<?php

declare(strict_types=1);

namespace Database\Seeders\Development;

use App\Models\HelpArticle;
use Illuminate\Database\Seeder;

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
}

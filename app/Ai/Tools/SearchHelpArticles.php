<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\HelpArticle;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

final class SearchHelpArticles implements Tool
{
    public function description(): string
    {
        return 'Search the help center for articles. Use when the user asks about how to do something, how the app works, or needs documentation.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Search query (keywords)')->required(),
            'limit' => $schema->integer()->description('Max number of results (default 5)'),
        ];
    }

    public function handle(Request $request): string
    {
        $query = (string) ($request['query'] ?? '');
        if ($query === '') {
            return 'Please provide a search query.';
        }

        $limit = (int) ($request['limit'] ?? 5);
        $limit = min(max(1, $limit), 10);

        $articles = HelpArticle::search($query)
            ->where('is_published', true)
            ->take($limit)->get();

        if ($articles->isEmpty()) {
            return 'No help articles found for that query.';
        }

        $lines = $articles->map(fn ($a): string => sprintf(
            '- %s (category: %s): %s',
            $a->title,
            $a->category ?? 'general',
            \Illuminate\Support\Str::limit(strip_tags((string) $a->excerpt), 120),
        ));

        return 'Help articles:'."\n".$lines->implode("\n");
    }
}

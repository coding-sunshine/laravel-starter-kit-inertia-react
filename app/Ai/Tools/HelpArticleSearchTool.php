<?php

declare(strict_types=1);

namespace App\Ai\Tools;

use App\Models\HelpArticle;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * RAG tool: search help articles to answer "how do I..." and documentation questions.
 * Uses Scout when available, otherwise database search.
 */
final class HelpArticleSearchTool implements Tool
{
    public function description(): Stringable|string
    {
        return 'Search the RRMCS help center and documentation. Use when users ask how to do something, where to find a feature, or for help with demurrage, penalties, indents, rakes, reconciliation, road dispatch, or railway receipts. Returns article titles, excerpts, and links.';
    }

    public function handle(Request $request): Stringable|string
    {
        $query = (string) ($request['query'] ?? '');
        if ($query === '') {
            return json_encode(['error' => 'Query is required'], JSON_THROW_ON_ERROR);
        }

        $limit = min(5, max(1, (int) ($request['limit'] ?? 5)));

        $articles = $this->search($query, $limit);

        $results = $articles->map(fn (HelpArticle $a): array => [
            'title' => $a->title,
            'slug' => $a->slug,
            'excerpt' => $a->excerpt ?? mb_substr(strip_tags((string) $a->content), 0, 200).'...',
            'category' => $a->category,
            'url' => route('help.show', $a->slug),
        ])->all();

        return json_encode([
            'query' => $query,
            'results' => $results,
            'count' => count($results),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Search query (e.g. "how to create indent", "demurrage calculation")')->required(),
            'limit' => $schema->integer()->description('Max number of articles to return (1–5, default 5)'),
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, HelpArticle>
     */
    private function search(string $query, int $limit): \Illuminate\Database\Eloquent\Collection
    {
        $driver = config('scout.driver', 'null');

        if ($driver !== 'null' && $driver !== 'collection') {
            return HelpArticle::search($query)
                ->where('is_published', true)
                ->take($limit)
                ->get();
        }

        $search = '%'.addcslashes($query, '%_').'%';
        $operator = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        return HelpArticle::query()
            ->published()
            ->where(function ($q) use ($search, $operator): void {
                $q->where('title', $operator, $search)
                    ->orWhere('excerpt', $operator, $search)
                    ->orWhere('content', $operator, $search)
                    ->orWhere('category', $operator, $search);
            })
            ->orderBy('order')
            ->limit($limit)
            ->get();
    }
}

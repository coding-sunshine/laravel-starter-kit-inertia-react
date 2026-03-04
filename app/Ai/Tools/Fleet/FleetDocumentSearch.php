<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\DocumentChunk;
use App\Models\Scopes\OrganizationScope;
use App\Services\VectorSearchService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Throwable;

final readonly class FleetDocumentSearch implements Tool
{
    private const int LIMIT = 8;

    public function __construct(
        private int $organizationId,
    ) {}

    public function description(): string
    {
        return 'Search fleet documents (MOT, V5C, insurance, service history) by natural language question. Use this to answer questions about vehicle documents, expiry dates, or compliance.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->required()->description('Natural language question about fleet documents'),
        ];
    }

    public function handle(Request $request): string
    {
        $query = (string) ($request['query'] ?? '');
        if ($query === '') {
            return 'Please provide a search query.';
        }

        DocumentChunk::query()->withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->organizationId);

        // Use professional-grade vector search service
        try {
            $queryEmbedding = Str::of($query)->toEmbeddings(provider: 'openrouter_embeddings', dimensions: 1536);
        } catch (Throwable $e) {
            return 'Embedding service unavailable: '.$e->getMessage();
        }
        if (! is_array($queryEmbedding) || $queryEmbedding === []) {
            return 'Could not generate query embedding.';
        }

        // Initialize professional vector search service
        $vectorSearch = new VectorSearchService($this->organizationId);

        // Perform optimized similarity search with caching
        $results = $vectorSearch->findSimilarDocuments(
            queryVector: $queryEmbedding,
            limit: self::LIMIT,
            threshold: 0.3 // Minimum similarity threshold for relevance
        );

        if ($results->isEmpty()) {
            return 'No matching document chunks found for this organization.';
        }

        $out = [];
        foreach ($results as $i => $chunk) {
            // Include similarity score for transparency (professional approach)
            $similarityPercent = round($chunk->similarity_score * 100, 1);
            $out[] = sprintf('[%d] (%s%% match) %s',
                $i + 1,
                $similarityPercent,
                mb_trim($chunk->content)
            );
        }

        return 'Relevant document excerpts (cite by number):'."\n\n".implode("\n\n", $out);
    }
}

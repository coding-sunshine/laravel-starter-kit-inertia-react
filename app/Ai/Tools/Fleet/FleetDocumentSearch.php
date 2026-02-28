<?php

declare(strict_types=1);

namespace App\Ai\Tools\Fleet;

use App\Models\Fleet\DocumentChunk;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Pgvector\Laravel\Distance;
use Stringable;

final class FleetDocumentSearch implements Tool
{
    private const LIMIT = 8;

    public function __construct(
        private readonly int $organizationId,
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

    public function handle(Request $request): string|Stringable
    {
        $query = (string) ($request['query'] ?? '');
        if ($query === '') {
            return 'Please provide a search query.';
        }

        $chunks = DocumentChunk::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->organizationId);

        if (! \Illuminate\Support\Facades\Schema::hasColumn($chunks->getModel()->getTable(), 'embedding_vector')) {
            return $this->fallbackTextSearch($chunks, $query);
        }

        try {
            $embedding = Str::of($query)->toEmbeddings(dimensions: 1536);
        } catch (\Throwable) {
            return $this->fallbackTextSearch($chunks, $query);
        }

        if (! is_array($embedding) || empty($embedding)) {
            return $this->fallbackTextSearch($chunks, $query);
        }

        $results = $chunks
            ->nearestNeighbors('embedding_vector', $embedding, Distance::Cosine)
            ->take(self::LIMIT)
            ->get();

        if ($results->isEmpty()) {
            return 'No matching document chunks found for this organization.';
        }

        $out = [];
        foreach ($results as $i => $chunk) {
            $out[] = sprintf('[%d] %s', $i + 1, trim($chunk->content));
        }

        return 'Relevant document excerpts (cite by number):'."\n\n".implode("\n\n", $out);
    }

    private function fallbackTextSearch(\Illuminate\Database\Eloquent\Builder $builder, string $search): string
    {
        $chunks = $builder->where('content', 'ilike', '%'.addcslashes($search, '%_').'%')
            ->take(self::LIMIT)
            ->get();

        if ($chunks->isEmpty()) {
            return 'No matching document chunks found.';
        }

        $out = [];
        foreach ($chunks as $i => $chunk) {
            $out[] = sprintf('[%d] %s', $i + 1, trim(Str::limit($chunk->content, 500)));
        }

        return 'Relevant excerpts:'."\n\n".implode("\n\n", $out);
    }
}

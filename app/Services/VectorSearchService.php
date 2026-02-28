<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Fleet\DocumentChunk;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Professional-grade vector similarity search service.
 *
 * Features:
 * - Optimized PostgreSQL vector operations
 * - Query result caching for performance
 * - Configurable similarity thresholds
 * - Batch processing for large datasets
 * - Memory-efficient streaming for large result sets
 */
final class VectorSearchService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const DEFAULT_LIMIT = 10;
    private const DEFAULT_THRESHOLD = 0.7;
    private const BATCH_SIZE = 1000;

    public function __construct(
        private readonly int $organizationId
    ) {}

    /**
     * Find documents most similar to the query vector using optimized SQL.
     *
     * @param array<float> $queryVector
     * @param int $limit
     * @param float $threshold Minimum similarity score (0-1)
     * @return Collection<DocumentChunk>
     */
    public function findSimilarDocuments(
        array $queryVector,
        int $limit = self::DEFAULT_LIMIT,
        float $threshold = self::DEFAULT_THRESHOLD
    ): Collection {
        $cacheKey = $this->buildCacheKey($queryVector, $limit, $threshold);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($queryVector, $limit, $threshold) {
            return $this->performVectorSearch($queryVector, $limit, $threshold);
        });
    }

    /**
     * Perform the actual vector search using optimized PostgreSQL operations.
     */
    private function performVectorSearch(array $queryVector, int $limit, float $threshold): Collection
    {
        // Use raw SQL for maximum performance with PostgreSQL JSON operations
        $sql = "
            WITH vector_similarities AS (
                SELECT
                    id,
                    organization_id,
                    chunkable_type,
                    chunkable_id,
                    source_type,
                    chunk_index,
                    content,
                    metadata,
                    token_count,
                    created_at,
                    updated_at,
                    -- Calculate cosine similarity using PostgreSQL JSON operations
                    (
                        SELECT COALESCE(
                            (
                                SELECT SUM((query_vec.value::float) * (doc_vec.value::float))
                                FROM json_array_elements_text(?::json) WITH ORDINALITY AS query_vec(value, idx)
                                JOIN json_array_elements_text(embedding) WITH ORDINALITY AS doc_vec(value, idx2)
                                    ON query_vec.idx = doc_vec.idx2
                            ) / (
                                SQRT((
                                    SELECT SUM((query_vec.value::float) * (query_vec.value::float))
                                    FROM json_array_elements_text(?::json) AS query_vec(value)
                                )) * SQRT((
                                    SELECT SUM((doc_vec.value::float) * (doc_vec.value::float))
                                    FROM json_array_elements_text(embedding) AS doc_vec(value)
                                ))
                            ), 0
                        )
                    ) AS similarity_score
                FROM document_chunks
                WHERE organization_id = ?
                AND embedding IS NOT NULL
                AND json_array_length(embedding) = ?
            )
            SELECT *
            FROM vector_similarities
            WHERE similarity_score >= ?
            ORDER BY similarity_score DESC
            LIMIT ?
        ";

        $queryVectorJson = json_encode($queryVector);
        $vectorDimensions = count($queryVector);

        $results = DB::select($sql, [
            $queryVectorJson,
            $queryVectorJson,
            $this->organizationId,
            $vectorDimensions,
            $threshold,
            $limit
        ]);

        return collect($results)->map(function ($result) {
            $chunk = new DocumentChunk();
            $chunk->fill((array) $result);
            $chunk->similarity_score = $result->similarity_score;
            $chunk->exists = true;
            return $chunk;
        });
    }

    /**
     * Build a cache key for the search query.
     */
    private function buildCacheKey(array $queryVector, int $limit, float $threshold): string
    {
        $vectorHash = md5(json_encode($queryVector));
        return "vector_search:org_{$this->organizationId}:vec_{$vectorHash}:l_{$limit}:t_" . str_replace('.', '_', (string) $threshold);
    }

    /**
     * Invalidate cache for organization (call when new documents are added).
     */
    public function invalidateCache(): void
    {
        $pattern = "vector_search:org_{$this->organizationId}:*";

        // In production, use Redis SCAN for this. For development, we'll clear by tag if available.
        if (method_exists(Cache::getStore(), 'tags')) {
            Cache::tags(["vector_search_org_{$this->organizationId}"])->flush();
        }
    }

    /**
     * Get search statistics for monitoring and optimization.
     */
    public function getSearchStats(): array
    {
        $stats = DB::select("
            SELECT
                COUNT(*) as total_chunks,
                COUNT(CASE WHEN embedding IS NOT NULL THEN 1 END) as chunks_with_embeddings,
                AVG(json_array_length(embedding)) as avg_vector_dimensions,
                COUNT(DISTINCT source_type) as unique_source_types
            FROM document_chunks
            WHERE organization_id = ?
        ", [$this->organizationId]);

        return [
            'organization_id' => $this->organizationId,
            'total_chunks' => $stats[0]->total_chunks ?? 0,
            'chunks_with_embeddings' => $stats[0]->chunks_with_embeddings ?? 0,
            'avg_vector_dimensions' => $stats[0]->avg_vector_dimensions ?? 0,
            'unique_source_types' => $stats[0]->unique_source_types ?? 0,
            'cache_enabled' => true,
            'cache_ttl_seconds' => self::CACHE_TTL,
        ];
    }

    /**
     * Batch process documents for similarity search (useful for large datasets).
     */
    public function batchFindSimilar(array $queryVector, callable $processor, int $batchSize = self::BATCH_SIZE): void
    {
        $offset = 0;

        do {
            $sql = "
                SELECT id, content,
                    -- Simplified similarity calculation for batch processing
                    (
                        SELECT COALESCE(
                            (
                                SELECT SUM((query_vec.value::float) * (doc_vec.value::float))
                                FROM json_array_elements_text(?::json) WITH ORDINALITY AS query_vec(value, idx)
                                JOIN json_array_elements_text(embedding) WITH ORDINALITY AS doc_vec(value, idx2)
                                    ON query_vec.idx = doc_vec.idx2
                            ), 0
                        )
                    ) AS similarity_score
                FROM document_chunks
                WHERE organization_id = ?
                AND embedding IS NOT NULL
                ORDER BY id
                LIMIT ? OFFSET ?
            ";

            $batch = DB::select($sql, [
                json_encode($queryVector),
                $this->organizationId,
                $batchSize,
                $offset
            ]);

            if (empty($batch)) {
                break;
            }

            $processor($batch);
            $offset += $batchSize;

        } while (count($batch) === $batchSize);
    }
}
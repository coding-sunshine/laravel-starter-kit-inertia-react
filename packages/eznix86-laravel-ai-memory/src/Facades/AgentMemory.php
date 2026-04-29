<?php

declare(strict_types=1);

namespace Eznix86\AI\Memory\Facades;

use Eznix86\AI\Memory\Models\Memory;
use Eznix86\AI\Memory\Services\MemoryManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;
use Laravel\Ai\Reranking;

/**
 * @method static Memory store(string $content, array<string, mixed> $context = [])
 * @method static Collection<string, Memory> recall(string $query, array<string, mixed> $context = [], ?int $limit = null)
 * @method static Collection<string, Memory> all(array<string, mixed> $context = [], int $limit = 100)
 * @method static bool forget(int $memoryId)
 * @method static int forgetAll(array<string, mixed> $context = [])
 *
 * @see MemoryManager
 */
final class AgentMemory extends Facade
{
    /**
     * Fake memory operations for testing.
     *
     * Internally fakes Embeddings (with deterministic vectors) and Reranking
     * so that store/recall work without real AI providers.
     *
     * @param  array<mixed>  $rerankingResponses
     */
    public static function fake(array $rerankingResponses = []): void
    {
        $dimensions = config('memory.dimensions', 1536);

        $embedding = self::makeDeterministicEmbedding($dimensions);

        Embeddings::fake(fn (EmbeddingsPrompt $prompt): array => array_map(fn (): array => $embedding, $prompt->inputs));

        Reranking::fake($rerankingResponses);
    }

    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return MemoryManager::class;
    }

    /**
     * Generate a deterministic, normalized embedding vector.
     *
     * All vectors are identical so cosine similarity ≈ 1.0,
     * ensuring recall always finds stored memories in tests.
     *
     * @return array<float>
     */
    protected static function makeDeterministicEmbedding(int $dimensions): array
    {
        $value = 1.0 / sqrt($dimensions);

        return array_fill(0, $dimensions, $value);
    }
}

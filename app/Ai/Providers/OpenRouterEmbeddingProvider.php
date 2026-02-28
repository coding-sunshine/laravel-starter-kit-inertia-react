<?php

declare(strict_types=1);

namespace App\Ai\Providers;

use App\Ai\Gateway\OpenRouterEmbeddingGateway;
use Illuminate\Contracts\Events\Dispatcher;
use Laravel\Ai\Contracts\Gateway\EmbeddingGateway;
use Laravel\Ai\Contracts\Providers\EmbeddingProvider;
use Laravel\Ai\Providers\Concerns\GeneratesEmbeddings;
use Laravel\Ai\Providers\Concerns\HasEmbeddingGateway;
use Laravel\Ai\Providers\Provider;

/**
 * Embeddings via OpenRouter only (OPENROUTER_API_KEY). No OpenAI key.
 */
final class OpenRouterEmbeddingProvider extends Provider implements EmbeddingProvider
{
    use GeneratesEmbeddings;
    use HasEmbeddingGateway;

    public function __construct(
        protected array $config,
        protected Dispatcher $events,
    ) {}

    public function name(): string
    {
        return 'openrouter_embeddings';
    }

    public function providerCredentials(): array
    {
        return [
            'key' => $this->config['key'] ?? '',
        ];
    }

    public function defaultEmbeddingsModel(): string
    {
        return $this->config['models']['embeddings']['default'] ?? 'openai/text-embedding-3-small';
    }

    public function defaultEmbeddingsDimensions(): int
    {
        return $this->config['models']['embeddings']['dimensions'] ?? 1536;
    }

    public function embeddingGateway(): EmbeddingGateway
    {
        return $this->embeddingGateway ??= new OpenRouterEmbeddingGateway;
    }
}

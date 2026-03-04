<?php

declare(strict_types=1);

namespace App\Ai\Gateway;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Laravel\Ai\Contracts\Gateway\EmbeddingGateway;
use Laravel\Ai\Contracts\Providers\EmbeddingProvider;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\EmbeddingsResponse;

final class OpenRouterEmbeddingGateway implements EmbeddingGateway
{
    private const string BASE_URL = 'https://openrouter.ai/api/v1';

    public function generateEmbeddings(
        EmbeddingProvider $provider,
        string $model,
        array $inputs,
        int $dimensions
    ): EmbeddingsResponse {
        $key = $provider->providerCredentials()['key'] ?? '';
        throw_if($key === '', InvalidArgumentException::class, 'OpenRouter API key is required for embeddings. Set OPENROUTER_API_KEY in .env.');

        $payload = [
            'model' => $model,
            'input' => $inputs,
        ];
        if ($dimensions > 0) {
            $payload['dimensions'] = $dimensions;
        }

        $response = Http::baseUrl(self::BASE_URL)
            ->withHeaders([
                'Authorization' => 'Bearer '.$key,
                'Content-Type' => 'application/json',
            ])
            ->post('/embeddings', $payload)
            ->throw();

        $data = $response->json();
        $embeddings = new Collection($data['data'] ?? [])->pluck('embedding')->all();

        return new EmbeddingsResponse(
            $embeddings,
            (int) ($data['usage']['total_tokens'] ?? 0),
            new Meta($provider->name(), $model),
        );
    }
}

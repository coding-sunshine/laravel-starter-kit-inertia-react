<?php

declare(strict_types=1);

namespace App\Services;

use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Structured\PendingRequest as StructuredPendingRequest;
use Prism\Prism\Text\PendingRequest;
use Prism\Prism\Text\TextResponse;
use Prism\Relay\Facades\Relay;

/**
 * Service for interacting with Prism AI providers.
 *
 * This service provides a convenient wrapper around Prism for common AI operations.
 * It defaults to OpenRouter but can be configured to use any Prism provider.
 */
final readonly class PrismService
{
    /**
     * Get the default provider from configuration.
     */
    public function defaultProvider(): Provider
    {
        $providerName = config('prism.defaults.provider', 'openrouter');

        return Provider::from($providerName);
    }

    /**
     * Get the default model from configuration.
     */
    public function defaultModel(): string
    {
        return config('prism.defaults.model', 'openai/gpt-4o-mini');
    }

    /**
     * Create a text generation request using OpenRouter.
     *
     * @param  string  $model  The model to use (e.g., 'openai/gpt-4o-mini')
     */
    public function text(?string $model = null): PendingRequest
    {
        return Prism::text()->using($this->defaultProvider(), $model ?? $this->defaultModel());
    }

    /**
     * Generate text from a prompt using OpenRouter.
     *
     * @param  string  $prompt  The prompt to send
     * @param  string|null  $model  The model to use (defaults to config)
     */
    public function generate(string $prompt, ?string $model = null): TextResponse
    {
        return $this->text($model)
            ->withPrompt($prompt)
            ->asText();
    }

    /**
     * Create a structured output request.
     *
     * @param  string|null  $model  The model to use (defaults to config)
     */
    public function structured(?string $model = null): StructuredPendingRequest
    {
        return Prism::structured()->using($this->defaultProvider(), $model ?? $this->defaultModel());
    }

    /**
     * Generate structured output from a prompt.
     *
     * @param  string  $prompt  The prompt to send
     * @param  string|object  $schema  The schema for structured output
     * @param  string|null  $model  The model to use (defaults to config)
     */
    public function generateStructured(string $prompt, string|object $schema, ?string $model = null): mixed
    {
        return $this->structured($model)
            ->withPrompt($prompt)
            ->withSchema($schema)
            ->asStructured();
    }

    /**
     * Create a text request with MCP tools from Relay.
     *
     * @param  string|array<int, string>  $servers  MCP server name(s) to get tools from
     * @param  string|null  $model  The model to use (defaults to config)
     */
    public function withTools(string|array $servers, ?string $model = null): PendingRequest
    {
        $tools = is_array($servers)
            ? array_merge(...array_map(fn (string $server) => Relay::tools($server), $servers))
            : Relay::tools($servers);

        return $this->text($model)->withTools($tools);
    }

    /**
     * Generate text using a different provider.
     *
     * @param  Provider  $provider  The provider to use
     * @param  string  $model  The model to use
     */
    public function using(Provider $provider, string $model): PendingRequest
    {
        return Prism::text()->using($provider, $model);
    }
}

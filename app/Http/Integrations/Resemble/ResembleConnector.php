<?php

declare(strict_types=1);

namespace App\Http\Integrations\Resemble;

use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;

/**
 * Saloon connector for the Resemble.ai API.
 *
 * Voice cloning integration for personalised AI voice agents.
 * Deferred if RESEMBLE_API_KEY is not set.
 *
 * @see https://docs.app.resemble.ai/docs
 * @see docs/developer/backend/saloon.md
 */
final class ResembleConnector extends Connector
{
    public static function isConfigured(): bool
    {
        return ! empty(config('services.resemble.api_key'));
    }

    public function resolveBaseUrl(): string
    {
        return 'https://app.resemble.ai/api/v2';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected function defaultAuth(): TokenAuthenticator
    {
        return new TokenAuthenticator((string) config('services.resemble.api_key'));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Integrations\ExampleApi;

use Saloon\Http\Connector;

/**
 * Example Saloon connector for the JSONPlaceholder API.
 * Use this as a template when adding real third-party API integrations.
 *
 * @see https://jsonplaceholder.typicode.com/
 * @see docs/developer/backend/saloon.md
 */
final class ExampleApiConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return config('services.example_api.url', 'https://jsonplaceholder.typicode.com');
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Integrations\Xero;

use Saloon\Http\Connector;

/**
 * Saloon connector for the Xero API.
 *
 * @see https://developer.xero.com/documentation/api/accounting/overview
 * @see docs/developer/backend/saloon.md
 */
final class XeroConnector extends Connector
{
    public static function isConfigured(): bool
    {
        return ! empty(config('services.xero.client_id'));
    }

    public function resolveBaseUrl(): string
    {
        return 'https://api.xero.com';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}

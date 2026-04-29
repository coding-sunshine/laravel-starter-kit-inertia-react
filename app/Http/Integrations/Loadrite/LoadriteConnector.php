<?php

declare(strict_types=1);

namespace App\Http\Integrations\Loadrite;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

final class LoadriteConnector extends Connector
{
    use AcceptsJson;

    public function __construct(private readonly string $accessToken) {}

    public function resolveBaseUrl(): string
    {
        return 'https://apicloud.loadrite-myinsighthq.com';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer '.$this->accessToken,
            'Content-Type' => 'application/json',
        ];
    }
}

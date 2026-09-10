<?php

declare(strict_types=1);

namespace App\Http\Integrations\Loadrite\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetDowntimeEventsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $site,
        private readonly string $fromLocalTime,
        private readonly string $toLocalTime,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v3/Downtime';
    }

    protected function defaultQuery(): array
    {
        return [
            'Site' => $this->site,
            'FromLocalTime' => $this->fromLocalTime,
            'ToLocalTime' => $this->toLocalTime,
        ];
    }
}

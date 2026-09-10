<?php

declare(strict_types=1);

namespace App\Http\Integrations\Loadrite\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Loadrite enforces a 7-day historic-data limit on this endpoint.
 * Callers must pass a `from` no older than now()-7 days.
 */
final class GetPositionsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $site,
        private readonly ?string $fromLocalTime = null,
        private readonly ?string $toLocalTime = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/Positions';
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'Site' => $this->site,
            'FromLocalTime' => $this->fromLocalTime,
            'ToLocalTime' => $this->toLocalTime,
        ], fn ($v) => $v !== null);
    }
}

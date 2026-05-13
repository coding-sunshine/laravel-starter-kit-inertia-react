<?php

declare(strict_types=1);

namespace App\Http\Integrations\Loadrite\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetLoadingEventsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $site,
        private readonly ?string $fromLocalTime = null,
        private readonly ?string $toLocalTime = null,
        private readonly ?int $page = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/Loading';
    }

    protected function defaultQuery(): array
    {
        return array_filter([
            'Site' => $this->site,
            'FromLocalTime' => $this->fromLocalTime,
            'ToLocalTime' => $this->toLocalTime,
            'Page' => $this->page,
        ], fn ($v) => $v !== null);
    }
}

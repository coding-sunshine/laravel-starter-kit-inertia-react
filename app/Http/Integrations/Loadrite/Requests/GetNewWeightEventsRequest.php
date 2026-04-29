<?php

declare(strict_types=1);

namespace App\Http\Integrations\Loadrite\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetNewWeightEventsRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $from) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/NewWeight';
    }

    protected function defaultQuery(): array
    {
        return ['from' => $this->from];
    }
}

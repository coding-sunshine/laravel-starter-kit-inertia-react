<?php

declare(strict_types=1);

namespace App\Http\Integrations\Loadrite\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class GetContextRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/api/v2/context';
    }
}

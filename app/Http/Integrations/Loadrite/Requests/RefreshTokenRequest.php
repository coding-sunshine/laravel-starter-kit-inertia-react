<?php

declare(strict_types=1);

namespace App\Http\Integrations\Loadrite\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

final class RefreshTokenRequest extends Request
{
    protected Method $method = Method::POST;

    public function __construct(private readonly string $refreshToken) {}

    public function resolveEndpoint(): string
    {
        return '/api/v2/auth/refresh-token';
    }

    protected function defaultBody(): array
    {
        return ['refreshToken' => $this->refreshToken];
    }
}

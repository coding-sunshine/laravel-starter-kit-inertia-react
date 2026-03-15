<?php

declare(strict_types=1);

namespace App\Http\Integrations\Xero\Requests;

use Override;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasFormBody;

/**
 * Refresh a Xero OAuth access token.
 *
 * Note: This uses the Xero identity endpoint, not the API base URL.
 */
final class RefreshTokenRequest extends Request implements HasBody
{
    use HasFormBody;

    #[Override]
    protected Method $method = Method::POST;

    public function __construct(private readonly string $refreshToken) {}

    public function resolveEndpoint(): string
    {
        return 'https://identity.xero.com/connect/token';
    }

    protected function defaultBody(): array
    {
        return [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refreshToken,
            'client_id' => config('services.xero.client_id'),
            'client_secret' => config('services.xero.client_secret'),
        ];
    }
}

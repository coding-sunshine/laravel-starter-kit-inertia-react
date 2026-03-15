<?php

declare(strict_types=1);

namespace App\Http\Integrations\Xero\Requests;

use Override;
use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Fetch all contacts from a Xero tenant.
 */
final class GetContactsRequest extends Request
{
    #[Override]
    protected Method $method = Method::GET;

    public function __construct(private readonly string $tenantId) {}

    public function resolveEndpoint(): string
    {
        return '/api.xro/2.0/Contacts';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Xero-Tenant-Id' => $this->tenantId,
        ];
    }
}

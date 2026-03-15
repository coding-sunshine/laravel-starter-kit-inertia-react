<?php

declare(strict_types=1);

namespace App\Http\Integrations\Xero\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

/**
 * Fetch invoices from a Xero tenant.
 */
final class GetInvoicesRequest extends Request
{
    protected Method $method = Method::GET;

    public function __construct(private readonly string $tenantId) {}

    public function resolveEndpoint(): string
    {
        return '/api.xro/2.0/Invoices';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Xero-Tenant-Id' => $this->tenantId,
        ];
    }
}

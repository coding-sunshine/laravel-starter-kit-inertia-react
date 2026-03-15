<?php

declare(strict_types=1);

namespace App\Http\Integrations\Xero\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

/**
 * Create or update a contact in Xero.
 */
final class CreateOrUpdateContactRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<string, mixed>  $contactData
     */
    public function __construct(
        private readonly string $tenantId,
        private readonly array $contactData
    ) {}

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

    protected function defaultBody(): array
    {
        return [
            'Contacts' => [$this->contactData],
        ];
    }
}

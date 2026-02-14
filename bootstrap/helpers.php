<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Services\PrismService;
use App\Services\TenantContext;

if (! function_exists('ai')) {
    /**
     * Get a PrismService instance for AI operations.
     */
    function ai(): PrismService
    {
        return app(PrismService::class);
    }
}

if (! function_exists('tenant')) {
    /**
     * Get the current organization (tenant) or null.
     */
    function tenant(): ?Organization
    {
        return TenantContext::get();
    }
}

if (! function_exists('tenant_id')) {
    /**
     * Get the current organization ID or null.
     */
    function tenant_id(): ?int
    {
        return TenantContext::id();
    }
}

if (! function_exists('tenant_term')) {
    /**
     * Get the tenant term (singular or plural) for UI.
     */
    function tenant_term(bool $plural = false): string
    {
        $key = $plural ? 'tenancy.term_plural' : 'tenancy.term';

        return (string) config($key, $plural ? 'Organizations' : 'Organization');
    }
}

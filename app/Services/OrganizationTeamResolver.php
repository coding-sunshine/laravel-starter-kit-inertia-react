<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

/**
 * Resolves the current organization (team) ID for Spatie Permission.
 * Returns 0 when no tenant context (global roles); otherwise TenantContext::id().
 */
final class OrganizationTeamResolver implements PermissionsTeamResolver
{
    public function getPermissionsTeamId(): int
    {
        $id = TenantContext::id();

        return $id ?? 0;
    }

    public function setPermissionsTeamId(int|string|Model|null $id): void
    {
        // Tenant context is set by middleware/actions; no-op here.
    }
}

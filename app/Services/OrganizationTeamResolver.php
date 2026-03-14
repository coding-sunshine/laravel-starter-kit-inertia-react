<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

/**
 * Resolves the current organization (team) ID for Spatie Permission.
 * RRMMS uses only global roles (organization_id = 0); always return 0 so
 * getAllPermissions() / can() use global role assignments for sidebar and gates.
 */
final class OrganizationTeamResolver implements PermissionsTeamResolver
{
    private const int GLOBAL_TEAM_ID = 0;

    public function getPermissionsTeamId(): int
    {
        return self::GLOBAL_TEAM_ID;
    }

    public function setPermissionsTeamId(int|string|Model|null $id): void
    {
        // Tenant context is set by middleware/actions; no-op here.
    }
}

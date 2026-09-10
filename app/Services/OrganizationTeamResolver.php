<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

/**
 * Resolves the current organization (team) ID for Spatie Permission.
 * Defaults to the global team (organization_id = 0) so getAllPermissions() /
 * can() use global role assignments unless code explicitly switches the team
 * (policies, actions, and Organization::addMember use a set/restore pattern).
 */
final class OrganizationTeamResolver implements PermissionsTeamResolver
{
    private const int GLOBAL_TEAM_ID = 0;

    private int|string|null $teamId = null;

    public function getPermissionsTeamId(): int|string
    {
        return $this->teamId ?? self::GLOBAL_TEAM_ID;
    }

    public function setPermissionsTeamId(int|string|Model|null $id): void
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }

        $this->teamId = $id;
    }
}

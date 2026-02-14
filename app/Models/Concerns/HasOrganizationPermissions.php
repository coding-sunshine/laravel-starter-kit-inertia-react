<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Organization;
use App\Services\Permission\PermissionService;
use App\Services\TenantContext;
use Illuminate\Support\Collection;

/**
 * Organization-scoped permission checks.
 *
 * Usage:
 *   $user->canInOrganization('org.members.invite', $organization);
 *   $user->canInCurrentOrganization('org.billing.view');
 *   $user->isOrganizationOwner($organization);
 */
trait HasOrganizationPermissions
{
    public function canInOrganization(string $permission, ?Organization $organization = null): bool
    {
        return resolve(PermissionService::class)->canInOrganization($this, $permission, $organization);
    }

    public function canInCurrentOrganization(string $permission): bool
    {
        return $this->canInOrganization($permission, TenantContext::organization());
    }

    /**
     * @param  array<string>  $permissions
     */
    public function canAnyInOrganization(array $permissions, ?Organization $organization = null): bool
    {
        return resolve(PermissionService::class)->canAnyInOrganization($this, $permissions, $organization);
    }

    /**
     * @param  array<string>  $permissions
     */
    public function canAllInOrganization(array $permissions, ?Organization $organization = null): bool
    {
        return resolve(PermissionService::class)->canAllInOrganization($this, $permissions, $organization);
    }

    /**
     * @return Collection<int, string>
     */
    public function getOrganizationPermissions(?Organization $organization = null): Collection
    {
        return resolve(PermissionService::class)->getOrganizationPermissions($this, $organization);
    }

    public function hasOrganizationRole(string $role, ?Organization $organization = null): bool
    {
        $org = $organization ?? TenantContext::organization();

        if (! $org instanceof Organization) {
            return false;
        }

        return in_array($role, $this->roleNamesInOrganization($org), true);
    }

    public function isOrganizationOwner(?Organization $organization = null): bool
    {
        $organization ??= TenantContext::organization();

        if (! $organization instanceof Organization) {
            return false;
        }

        return $organization->isOwner($this);
    }

    public function isOrganizationAdmin(?Organization $organization = null): bool
    {
        $organization ??= TenantContext::organization();

        if (! $organization instanceof Organization) {
            return false;
        }

        return $organization->hasAdmin($this);
    }

    /**
     * Role names in organization (from Spatie team-scoped roles).
     * Owner is not a role; use isOrganizationOwner() for owner check.
     *
     * @return array<string>
     */
    public function roleNamesInOrganization(Organization|int $organization): array
    {
        $org = $organization instanceof Organization
            ? $organization
            : Organization::query()->find($organization);

        if (! $org instanceof Organization) {
            return [];
        }

        $teamKey = config('permission.column_names.team_foreign_key');
        $tableNames = config('permission.table_names');

        return (array) \Illuminate\Support\Facades\DB::table($tableNames['model_has_roles'])
            ->join($tableNames['roles'], $tableNames['roles'].'.id', '=', $tableNames['model_has_roles'].'.role_id')
            ->where($tableNames['model_has_roles'].'.model_id', $this->id)
            ->where($tableNames['model_has_roles'].'.model_type', self::class)
            ->where($tableNames['model_has_roles'].'.'.$teamKey, $org->id)
            ->pluck($tableNames['roles'].'.name')
            ->all();
    }
}

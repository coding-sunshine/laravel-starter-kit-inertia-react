<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Organization;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Blade directives for organization permissions and roles:
 *
 * @canOrg('permission', $organization?)
 *
 * @cannotOrg('permission', $organization?)
 *
 * @canAnyOrg(['perm1','perm2'], $organization?)
 *
 * @canAllOrg(['perm1','perm2'], $organization?)
 *
 * @isOrgOwner($organization?)
 *
 * @isOrgAdmin($organization?)
 *
 * @isOrgMember($organization?)
 *
 * @isOrgRole('role', $organization?)
 */
final class PermissionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::if('canOrg', fn (string $permission, ?Organization $organization = null): bool => auth()->user()?->canInOrganization($permission, $organization) ?? false);
        Blade::if('cannotOrg', fn (string $permission, ?Organization $organization = null): bool => ! (auth()->user()?->canInOrganization($permission, $organization) ?? false));
        Blade::if('canAnyOrg', fn (array $permissions, ?Organization $organization = null): bool => auth()->user()?->canAnyInOrganization($permissions, $organization) ?? false);
        Blade::if('canAllOrg', fn (array $permissions, ?Organization $organization = null): bool => auth()->user()?->canAllInOrganization($permissions, $organization) ?? false);
        Blade::if('isOrgOwner', fn (?Organization $organization = null): bool => auth()->user()?->isOrganizationOwner($organization) ?? false);
        Blade::if('isOrgAdmin', fn (?Organization $organization = null): bool => auth()->user()?->isOrganizationAdmin($organization) ?? false);
        Blade::if('isOrgMember', fn (?Organization $organization = null): bool => $org = $organization ?? TenantContext::organization() && auth()->user() && $org->hasMember(auth()->user()));
        Blade::if('isOrgRole', fn (string $role, ?Organization $organization = null): bool => auth()->user()?->hasOrganizationRole($role, $organization) ?? false);
    }
}

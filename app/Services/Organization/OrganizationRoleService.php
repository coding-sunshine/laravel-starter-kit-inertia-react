<?php

declare(strict_types=1);

namespace App\Services\Organization;

use App\Models\Organization;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Manages organization-scoped roles and their permissions from organization-permissions.json.
 */
final class OrganizationRoleService
{
    private const string GUARD = 'web';

    /**
     * Get permission names for the given org role names from JSON config.
     *
     * @param  array<string>  $roleNames  e.g. ['owner','admin'] or ['member']
     * @return array<string>
     */
    public function getPermissionNamesForRoles(array $roleNames): array
    {
        $config = $this->loadConfig();
        $names = [];

        foreach ($config['organization_permissions'] ?? [] as $category) {
            foreach ($category['permissions'] ?? [] as $perm) {
                $permRoles = $perm['roles'] ?? [];
                if (array_intersect($roleNames, $permRoles) !== []) {
                    $names[] = $perm['name'];
                }
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Assign permissions from JSON to existing org roles.
     * Call after ensureOrgRolesExist or when syncing permissions.
     */
    public function syncRolePermissions(Organization $organization): void
    {
        $teamKey = config('permission.column_names.team_foreign_key');

        foreach (['admin', 'member'] as $roleName) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', self::GUARD)
                ->where($teamKey, $organization->id)
                ->first();

            if (! $role instanceof Role) {
                continue;
            }

            $roleNames = $roleName === 'admin' ? ['owner', 'admin'] : ['member'];
            $permissionNames = $this->getPermissionNamesForRoles($roleNames);

            $permissionIds = Permission::query()
                ->where('guard_name', self::GUARD)
                ->whereIn('name', $permissionNames)
                ->pluck('id')
                ->all();

            $role->permissions()->sync($permissionIds);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function loadConfig(): array
    {
        $path = database_path('seeders/data/organization-permissions.json');
        throw_unless(is_file($path), RuntimeException::class, 'Organization permissions config not found: '.$path);

        $content = file_get_contents($path);
        throw_if($content === false, RuntimeException::class, 'Failed to read organization permissions config');

        $decoded = json_decode($content, true);
        throw_if(json_last_error() !== JSON_ERROR_NONE, RuntimeException::class, 'Invalid JSON in organization permissions config');

        return $decoded;
    }
}

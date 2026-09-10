<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class ManagerBriefPermissionsSeeder extends Seeder
{
    private const string GUARD = 'web';

    private const int GLOBAL_TEAM_ID = 0;

    /**
     * Seeder dependencies (base roles/permissions must exist first).
     *
     * @var array<string>
     */
    public array $dependencies = ['RolesAndPermissionsSeeder'];

    public function run(): void
    {
        resolve(PermissionRegistrar::class)->forgetCachedPermissions();

        $teamKey = config('permission.column_names.team_foreign_key', 'organization_id');

        // Ensure permission rows exist (idempotent).
        $view = Permission::query()->firstOrCreate(
            ['name' => 'sections.manager_brief.view', 'guard_name' => self::GUARD],
        );

        $refresh = Permission::query()->firstOrCreate(
            ['name' => 'sections.manager_brief.refresh', 'guard_name' => self::GUARD],
        );

        $roleAttrs = fn (string $name): array => [
            'name' => $name,
            'guard_name' => self::GUARD,
            $teamKey => self::GLOBAL_TEAM_ID,
        ];

        // Grant view to: super-admin, admin.
        // Note: 'manager' and 'ops-lead' roles do not exist in this project; granting to super-admin + admin only.
        $superAdmin = Role::query()->firstOrCreate($roleAttrs('super-admin'));
        $admin = Role::query()->firstOrCreate($roleAttrs('admin'));

        $superAdmin->givePermissionTo($view);
        $superAdmin->givePermissionTo($refresh);

        $admin->givePermissionTo($view);

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

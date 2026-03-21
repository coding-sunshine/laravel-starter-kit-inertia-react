<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Services\PermissionCategoryResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolesAndPermissionsSeeder extends Seeder
{
    private const string GUARD = 'web';

    private const int GLOBAL_TEAM_ID = 0;

    public function run(): void
    {
        resolve(PermissionRegistrar::class)->forgetCachedPermissions();

        $teamKey = config('permission.column_names.team_foreign_key', 'organization_id');
        $roleAttrs = fn (string $name): array => [
            'name' => $name,
            'guard_name' => self::GUARD,
            $teamKey => self::GLOBAL_TEAM_ID,
        ];

        $corePermissions = [
            'bypass-permissions',
            'access admin panel',
            'view users',
            'create users',
            'edit users',
            'delete users',
        ];

        foreach ($corePermissions as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
        }

        $this->call(SectionPermissionsSeeder::class);

        $superAdmin = Role::query()->firstOrCreate($roleAttrs('super-admin'));
        $superAdmin->givePermissionTo('bypass-permissions');

        $admin = Role::query()->firstOrCreate($roleAttrs('admin'));
        $userRole = Role::query()->firstOrCreate($roleAttrs('user'));
        $dispatchManageAdmin = Role::query()->firstOrCreate($roleAttrs('dispatch-manage-admin'));
        $viewerRole = Role::query()->firstOrCreate($roleAttrs('viewer'));
        $emptyWeighmentShiftRole = Role::query()->firstOrCreate($roleAttrs('empty-weighment-shift'));

        $defaultRolePerms = config('permission.default_role_permissions', []);
        if (is_array($defaultRolePerms) && $defaultRolePerms !== []) {
            $userRole->syncPermissions(
                array_filter($defaultRolePerms, fn (string $name): bool => Permission::query()->where('name', $name)->where('guard_name', self::GUARD)->exists())
            );
        } else {
            $userRole->syncPermissions([
                'sections.railway_siding_record_data.view',
                'sections.railway_siding_record_data.create',
                'sections.railway_siding_record_data.update',
            ]);
        }

        $dispatchManageAdmin->syncPermissions([
            'sections.mines_dispatch_data.view',
            'sections.mines_dispatch_data.upload',
            'sections.transport.create',
            'sections.transport.update',
            'sections.railway_siding_record_data.view',
            'sections.railway_siding_empty_weighment.view',
            'sections.railway_siding_empty_weighment.create',
            'sections.railway_siding_empty_weighment.update',
        ]);

        $viewerRole->syncPermissions(['sections.dashboard.view']);

        $emptyWeighmentShiftRole->syncPermissions([
            'sections.railway_siding_empty_weighment.view',
            'sections.railway_siding_empty_weighment.create',
            'sections.railway_siding_empty_weighment.update',
        ]);

        $adminCorePerms = [
            'access admin panel',
            'view users',
            'create users',
            'edit users',
            'delete users',
        ];
        $adminSectionPerms = [
            'sections.rakes.view',
            'sections.indents.view',
            'sections.railway_receipts.view',
            'sections.weighments.view',
        ];
        if (config('permission.permission_categories_enabled', false)) {
            $resolver = resolve(PermissionCategoryResolver::class);
            $adminPerms = $resolver->getPermissionsForRole('admin');
            if ($adminPerms !== []) {
                $admin->syncPermissions(array_merge($adminCorePerms, $adminSectionPerms, $adminPerms));
            } else {
                $admin->syncPermissions(array_merge($adminCorePerms, $adminSectionPerms));
            }
        } else {
            $admin->syncPermissions(array_merge($adminCorePerms, $adminSectionPerms));
        }

        // Sync route-named permissions only when using route-based enforcement. RRMMS uses section-based permissions only.
        if (config('permission.route_based_enforcement', false)) {
            Artisan::call('permission:sync-routes', ['--silent' => true]);
        }

        // Sync org permissions from organization-permissions.json so org roles (admin/member) get org.* permissions.
        if (is_file(database_path('seeders/data/organization-permissions.json'))) {
            Artisan::call('permission:sync', ['--silent' => true]);
        }

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

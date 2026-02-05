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

    public function run(): void
    {
        resolve(PermissionRegistrar::class)->forgetCachedPermissions();

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

        $superAdmin = Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => self::GUARD]);
        $superAdmin->givePermissionTo('bypass-permissions');

        $admin = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => self::GUARD]);
        // "user" role is created with no permissions; authenticated routes (dashboard, settings, etc.) are in route_skip_patterns
        Role::query()->firstOrCreate(['name' => 'user', 'guard_name' => self::GUARD]);

        if (config('permission.permission_categories_enabled', false)) {
            $resolver = app(PermissionCategoryResolver::class);
            $adminPerms = $resolver->getPermissionsForRole('admin');
            if ($adminPerms !== []) {
                $admin->syncPermissions($adminPerms);
            } else {
                $admin->syncPermissions([
                    'access admin panel',
                    'view users',
                    'create users',
                    'edit users',
                    'delete users',
                ]);
            }
        } else {
            $admin->syncPermissions([
                'access admin panel',
                'view users',
                'create users',
                'edit users',
                'delete users',
            ]);
        }

        if (config('permission.route_based_enforcement', false)) {
            Artisan::call('permission:sync-routes', ['--silent' => true]);
        }

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

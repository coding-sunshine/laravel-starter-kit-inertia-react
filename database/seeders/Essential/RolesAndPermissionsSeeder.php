<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolesAndPermissionsSeeder extends Seeder
{
    private const string GUARD = 'web';

    public function run(): void
    {
        resolve(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'access admin panel',
            'view users',
            'create users',
            'edit users',
            'delete users',
        ];

        foreach ($permissions as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
        }

        Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => self::GUARD]);

        $admin = Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => self::GUARD]);

        Role::query()->firstOrCreate(['name' => 'user', 'guard_name' => self::GUARD]);

        $admin->givePermissionTo([
            'access admin panel',
            'view users',
            'create users',
            'edit users',
            'delete users',
        ]);

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

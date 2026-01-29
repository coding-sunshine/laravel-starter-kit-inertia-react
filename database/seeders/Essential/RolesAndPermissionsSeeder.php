<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolesAndPermissionsSeeder extends Seeder
{
    private const GUARD = 'web';

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'access admin panel',
            'view users',
            'create users',
            'edit users',
            'delete users',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
        }

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => self::GUARD]);

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => self::GUARD]);

        Role::firstOrCreate(['name' => 'user', 'guard_name' => self::GUARD]);

        $admin->givePermissionTo([
            'access admin panel',
            'view users',
            'create users',
            'edit users',
            'delete users',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

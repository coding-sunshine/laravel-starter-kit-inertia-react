<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class CrmRolesSeeder extends Seeder
{
    private const string GUARD = 'web';

    public function run(): void
    {
        resolve(PermissionRegistrar::class)->forgetCachedPermissions();

        $crmPermissions = [
            'view contacts',
            'create contacts',
            'edit contacts',
            'delete contacts',
            'view projects',
            'create projects',
            'edit projects',
            'delete projects',
        ];

        foreach ($crmPermissions as $name) {
            Permission::query()->firstOrCreate(['name' => $name, 'guard_name' => self::GUARD]);
        }

        $salesAgent = Role::query()->firstOrCreate(['name' => 'sales-agent', 'guard_name' => self::GUARD]);
        $salesAgent->givePermissionTo(array_filter($crmPermissions, fn (string $p): bool => str_starts_with($p, 'view ') || str_starts_with($p, 'create ') || str_starts_with($p, 'edit ')));

        $bdm = Role::query()->firstOrCreate(['name' => 'bdm', 'guard_name' => self::GUARD]);
        $bdm->syncPermissions(array_filter($crmPermissions, fn (string $p): bool => str_starts_with($p, 'view ') || str_starts_with($p, 'edit ')));

        $referralPartner = Role::query()->firstOrCreate(['name' => 'referral-partner', 'guard_name' => self::GUARD]);
        $referralPartner->syncPermissions(array_filter($crmPermissions, fn (string $p): bool => str_starts_with($p, 'view ')));

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

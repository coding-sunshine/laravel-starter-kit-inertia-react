<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;

final class RakeManagementRolePermissionSeeder extends Seeder
{
    /**
     * Seeder dependencies (base roles/permissions must exist first).
     *
     * @var array<string>
     */
    public array $dependencies = ['RolesAndPermissionsSeeder'];

    /**
     * Run the database seeds for Railway Rake Management Control System.
     * DISABLED: RRMMS uses only 5 roles from RolesAndPermissionsSeeder (super-admin, admin, user, dispatch-manage-admin, viewer).
     * Uncomment the body to re-enable legacy roles (super_admin, management, siding_in_charge, etc.) and rakes:* permissions.
     */
    public function run(): void
    {
        // No-op: RRMMS uses only 5 roles from RolesAndPermissionsSeeder.
        // Legacy body (super_admin, management, siding_in_charge, etc.) removed to avoid duplicate roles.
    }
}

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
     * Creates roles and permissions required for coal logistics operations.
     */
    public function run(): void
    {
        // Create roles (SOW §3: Mine Operator, Siding Operator, Siding In-Charge, Management, System Admin)
        $superAdmin = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $management = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'management', 'guard_name' => 'web']);
        $inCharge = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'siding_in_charge', 'guard_name' => 'web']);
        $operator = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'siding_operator', 'guard_name' => 'web']);
        $mineOperator = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'mine_operator', 'guard_name' => 'web']);
        $finance = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'finance', 'guard_name' => 'web']);

        // Define permissions for RRMCS
        $permissions = [
            // Rake Management
            'rakes:view', 'rakes:create', 'rakes:edit', 'rakes:delete',
            'rakes:manage-wagon-loading', 'rakes:manage-weighments',

            // RR Document Management
            'rr-documents:view', 'rr-documents:upload', 'rr-documents:verify',
            'rr-documents:reconcile', 'rr-documents:edit', 'rr-documents:delete',

            // Indent Management
            'indents:view', 'indents:create', 'indents:edit', 'indents:delete',

            // Stock & Vehicle Management
            'stock-ledger:view', 'stock-ledger:create',
            'vehicle-arrivals:view', 'vehicle-arrivals:create', 'vehicle-arrivals:edit',

            // Guard & Weighment Inspections
            'guard-inspections:view', 'guard-inspections:create', 'guard-inspections:edit',
            'weighments:view', 'weighments:create', 'weighments:edit',

            // Penalties & Demurrage
            'penalties:view', 'penalties:manage', 'penalties:reconcile',

            // Reports & Analytics
            'reports:view', 'reports:export',
            'analytics:view', 'dashboards:view',

            // Administration
            'sidings:manage', 'users:manage', 'roles:manage',
            'system:configure', 'system:audit',
        ];

        // Create all permissions
        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign permissions to roles
        // Super Admin: All permissions
        $superAdmin->syncPermissions($permissions);

        // Management: View and manage key operations (no system config)
        $management->syncPermissions([
            'rakes:view', 'rakes:edit',
            'rr-documents:view', 'rr-documents:verify',
            'indents:view', 'indents:edit',
            'stock-ledger:view',
            'vehicle-arrivals:view',
            'guard-inspections:view',
            'weighments:view',
            'penalties:view', 'penalties:manage',
            'reports:view', 'reports:export',
            'analytics:view', 'dashboards:view',
        ]);

        // Siding In-Charge: Full siding operations
        $inCharge->syncPermissions([
            'rakes:view', 'rakes:create', 'rakes:edit',
            'rakes:manage-wagon-loading', 'rakes:manage-weighments',
            'rr-documents:view', 'rr-documents:upload', 'rr-documents:verify', 'rr-documents:reconcile',
            'indents:view', 'indents:create', 'indents:edit',
            'stock-ledger:view', 'stock-ledger:create',
            'vehicle-arrivals:view', 'vehicle-arrivals:create', 'vehicle-arrivals:edit',
            'guard-inspections:view', 'guard-inspections:create', 'guard-inspections:edit',
            'weighments:view', 'weighments:create', 'weighments:edit',
            'penalties:view', 'penalties:reconcile',
            'reports:view', 'reports:export',
            'dashboards:view',
        ]);

        // Siding Operator: Day-to-day operations
        $operator->syncPermissions([
            'rakes:view', 'rakes:create', 'rakes:edit',
            'rakes:manage-wagon-loading',
            'rr-documents:view', 'rr-documents:upload',
            'indents:view', 'indents:create',
            'stock-ledger:view', 'stock-ledger:create',
            'vehicle-arrivals:view', 'vehicle-arrivals:create',
            'guard-inspections:view', 'guard-inspections:create',
            'weighments:view', 'weighments:create',
            'penalties:view',
            'dashboards:view',
        ]);

        // Mine Operator: View-only access for siding/rake data (may use this app to see dispatch status)
        $mineOperator->syncPermissions([
            'rakes:view',
            'indents:view',
            'stock-ledger:view',
            'vehicle-arrivals:view',
            'dashboards:view',
            'reports:view',
        ]);

        // Finance: Financial and reporting functions
        $finance->syncPermissions([
            'rakes:view',
            'rr-documents:view',
            'indents:view',
            'stock-ledger:view',
            'penalties:view', 'penalties:manage',
            'reports:view', 'reports:export',
            'analytics:view', 'dashboards:view',
        ]);
    }
}

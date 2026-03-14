<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class CrmRolesPermissionsSeeder extends Seeder
{
    private const string GUARD = 'web';

    /**
     * All CRM permissions grouped by category.
     *
     * @var array<string, array<int, string>>
     */
    private const array PERMISSIONS = [
        'dashboard' => [
            'contacts.view.dashboard',
        ],
        'contacts' => [
            'contacts.view',
            'contacts.create',
            'contacts.edit',
            'contacts.delete',
            'contacts.view.own',
            'contacts.edit.own',
            'contacts.export',
            'contacts.import',
            'contacts.merge',
        ],
        'property_portal' => [
            'projects.view',
            'projects.view.details',
            'projects.create',
            'projects.edit',
            'projects.delete',
            'lots.view',
            'lots.view.details',
            'lots.create',
            'lots.edit',
            'lots.delete',
            'potential_properties.view',
            'potential_properties.manage',
            'potential_properties.map',
        ],
        'sales_reservations' => [
            'reservations.view',
            'reservations.create',
            'reservations.edit',
            'sales.view',
            'sales.create',
            'commissions.view',
            'spr.view',
            'spr.manage',
        ],
        'tasks' => [
            'tasks.view',
            'tasks.view.own',
            'tasks.create',
            'tasks.edit',
            'tasks.delete',
        ],
        'enquiries' => [
            'enquiries.view',
            'enquiries.property_search.view',
            'enquiries.reservation.view',
            'enquiries.finance.view',
        ],
        'marketing' => [
            'marketing.view',
            'flyers.view',
            'flyers.create',
            'flyers.edit',
            'flyers.delete',
            'campaign_websites.view',
            'campaign_websites.create',
            'campaign_websites.edit',
            'campaign_websites.delete',
            'mail_lists.view',
            'mail_jobs.view',
            'websites.view',
        ],
        'reports' => [
            'reports.view',
            'reports.network_activity',
            'reports.notes_history',
            'reports.login_history',
            'reports.same_device',
            'reports.website',
            'reports.wp_website',
            'reports.campaign',
        ],
        'resources' => [
            'resources.view',
            'resources.create',
            'resources.edit',
            'resources.delete',
        ],
        'admin_system' => [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'roles.manage',
            'api_keys.view',
            'api_keys.manage',
            'ai_credits.view',
            'ai_credits.manage',
            'settings.system',
            'orgs.view_all',
            'orgs.manage',
            'agents.view',
            'co_living_projects.view',
        ],
    ];

    public function run(): void
    {
        resolve(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create all CRM permissions
        $allPermissions = array_merge(...array_values(self::PERMISSIONS));
        foreach ($allPermissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => self::GUARD]);
        }

        $this->seedSuperAdmin();
        $this->seedPiabAdmin();
        $this->seedSubscriber();
        $this->seedAgent();
        $this->seedSalesAgent();
        $this->seedBdm();
        $this->seedReferralPartner();
        $this->seedAffiliate();
        $this->seedPropertyManager();

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedSuperAdmin(): void
    {
        $role = Role::query()->firstOrCreate(['name' => 'superadmin', 'guard_name' => self::GUARD]);
        $allPermissions = array_merge(...array_values(self::PERMISSIONS));
        $role->syncPermissions($allPermissions);
    }

    private function seedPiabAdmin(): void
    {
        $role = Role::query()->firstOrCreate(['name' => 'piab_admin', 'guard_name' => self::GUARD]);
        $excluded = [
            'reports.same_device',
            'roles.manage',
            'settings.system',
            'orgs.manage',
        ];
        $allPermissions = array_merge(...array_values(self::PERMISSIONS));
        $role->syncPermissions(array_filter($allPermissions, fn (string $p): bool => ! in_array($p, $excluded)));
    }

    private function seedSubscriber(): void
    {
        $role = Role::query()->firstOrCreate(['name' => 'subscriber', 'guard_name' => self::GUARD]);
        $role->syncPermissions([
            'contacts.view.dashboard',
            'contacts.view', 'contacts.create', 'contacts.edit', 'contacts.delete', 'contacts.export', 'contacts.import',
            'projects.view', 'projects.view.details',
            'lots.view', 'lots.view.details',
            'potential_properties.map',
            'reservations.view', 'reservations.create',
            'sales.view',
            'commissions.view',
            'tasks.view.own', 'tasks.create', 'tasks.edit', 'tasks.delete',
            'enquiries.view', 'enquiries.property_search.view', 'enquiries.reservation.view', 'enquiries.finance.view',
            'marketing.view', 'flyers.view', 'flyers.create', 'flyers.edit', 'flyers.delete',
            'campaign_websites.view', 'campaign_websites.create', 'campaign_websites.edit', 'campaign_websites.delete',
            'mail_lists.view', 'mail_jobs.view', 'websites.view',
            'reports.view', 'reports.website', 'reports.wp_website', 'reports.campaign',
            'resources.view',
            'users.view', 'users.create',
            'api_keys.view', 'api_keys.manage',
            'ai_credits.view',
            'spr.view',
            'agents.view',
        ]);
    }

    private function seedAgent(): void
    {
        $role = Role::query()->firstOrCreate(['name' => 'agent', 'guard_name' => self::GUARD]);
        $role->syncPermissions([
            'contacts.view.dashboard',
            'contacts.view', 'contacts.create', 'contacts.edit', 'contacts.delete',
            'contacts.view.own', 'contacts.edit.own',
            'projects.view', 'projects.view.details',
            'lots.view', 'lots.view.details',
            'reservations.view',
            'sales.view',
            'commissions.view',
            'tasks.view.own', 'tasks.create', 'tasks.edit', 'tasks.delete',
            'enquiries.view', 'enquiries.reservation.view', 'enquiries.property_search.view', 'enquiries.finance.view',
            'resources.view',
            'mail_lists.view',
            'co_living_projects.view',
        ]);
    }

    private function seedSalesAgent(): void
    {
        // sales_agent is an alias for agent — identical permissions
        $role = Role::query()->firstOrCreate(['name' => 'sales_agent', 'guard_name' => self::GUARD]);
        $role->syncPermissions([
            'contacts.view.dashboard',
            'contacts.view', 'contacts.create', 'contacts.edit', 'contacts.delete',
            'contacts.view.own', 'contacts.edit.own',
            'projects.view', 'projects.view.details',
            'lots.view', 'lots.view.details',
            'reservations.view',
            'sales.view',
            'commissions.view',
            'tasks.view.own', 'tasks.create', 'tasks.edit', 'tasks.delete',
            'enquiries.view', 'enquiries.reservation.view', 'enquiries.property_search.view', 'enquiries.finance.view',
            'resources.view',
            'mail_lists.view',
            'co_living_projects.view',
        ]);
    }

    private function seedBdm(): void
    {
        $role = Role::query()->firstOrCreate(['name' => 'bdm', 'guard_name' => self::GUARD]);
        $role->syncPermissions([
            'contacts.view.dashboard',
            'contacts.view', 'contacts.create', 'contacts.edit', 'contacts.delete',
            'projects.view', 'projects.view.details',
            'lots.view', 'lots.view.details',
            'reservations.view', 'reservations.create',
            'sales.view',
            'commissions.view',
            'tasks.view.own', 'tasks.create', 'tasks.edit', 'tasks.delete',
            'enquiries.view', 'enquiries.property_search.view', 'enquiries.reservation.view', 'enquiries.finance.view',
            'resources.view',
            'mail_lists.view',
            'users.view', 'users.create',
        ]);
    }

    private function seedReferralPartner(): void
    {
        $role = Role::query()->firstOrCreate(['name' => 'referral_partner', 'guard_name' => self::GUARD]);
        $role->syncPermissions([
            'contacts.view.dashboard',
            'contacts.view.own', 'contacts.create', 'contacts.edit.own',
            'tasks.view.own', 'tasks.create', 'tasks.edit', 'tasks.delete',
            'enquiries.view', 'enquiries.property_search.view',
            'resources.view',
            'commissions.view',
            'mail_lists.view',
        ]);
    }

    private function seedAffiliate(): void
    {
        $role = Role::query()->firstOrCreate(['name' => 'affiliate', 'guard_name' => self::GUARD]);
        $role->syncPermissions([
            'contacts.view.dashboard',
            'contacts.view', 'contacts.create', 'contacts.edit', 'contacts.delete',
            'projects.view', 'projects.view.details',
            'lots.view', 'lots.view.details',
            'resources.view',
            'commissions.view',
            'tasks.view.own', 'tasks.create', 'tasks.edit', 'tasks.delete',
            'mail_lists.view',
            'users.view', 'users.create',
        ]);
    }

    private function seedPropertyManager(): void
    {
        $role = Role::query()->firstOrCreate(['name' => 'property_manager', 'guard_name' => self::GUARD]);
        $role->syncPermissions([
            'contacts.view.dashboard',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'projects.view', 'projects.create', 'projects.edit', 'projects.delete', 'projects.view.details',
            'lots.view', 'lots.create', 'lots.edit', 'lots.delete', 'lots.view.details',
            'potential_properties.view', 'potential_properties.manage', 'potential_properties.map',
            'tasks.view.own', 'tasks.create', 'tasks.edit', 'tasks.delete',
            'enquiries.view', 'enquiries.property_search.view', 'enquiries.reservation.view', 'enquiries.finance.view',
            'resources.view',
            'mail_lists.view',
        ]);
    }
}

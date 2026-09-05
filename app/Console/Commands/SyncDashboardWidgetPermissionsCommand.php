<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Dashboard\DashboardWidgetPermissions;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'permission:sync-dashboard-widgets')]
final class SyncDashboardWidgetPermissionsCommand extends Command
{
    protected $signature = 'permission:sync-dashboard-widgets
                            {--dry-run : Show actions without writing to the database}
                            {--no-backfill : Do not assign widget permissions to roles that already have sections.dashboard.view}';

    protected $description = 'Create dashboard widget permissions (dashboard.widgets.*) and optionally backfill roles with dashboard access';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $backfill = ! (bool) $this->option('no-backfill');

        $guard = (string) config('permission.default_guard_name', 'web');
        $definitionNames = array_keys(DashboardWidgetPermissions::definitions());

        if ($dryRun) {
            $this->warn('DRY RUN — no database changes');
        }

        if (! $dryRun) {
            resolve(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $createdCount = 0;
        foreach ($definitionNames as $name) {
            if ($dryRun) {
                $exists = Permission::query()
                    ->where('guard_name', $guard)
                    ->where('name', $name)
                    ->exists();
                if (! $exists) {
                    $this->line("Would create permission: {$name}");
                    $createdCount++;
                }

                continue;
            }

            $permission = Permission::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => $guard,
            ]);
            if ($permission->wasRecentlyCreated) {
                $this->info("Created permission: {$name}");
                $createdCount++;
            }
        }

        if ($dryRun) {
            $this->line(sprintf('Would create %d new permission(s).', $createdCount));
        } else {
            resolve(PermissionRegistrar::class)->forgetCachedPermissions();
            if ($createdCount === 0) {
                $this->info('All dashboard widget permissions already exist.');
            }
        }

        if (! $backfill) {
            if (! $dryRun) {
                $this->info('Skipped role backfill (--no-backfill).');
            }

            return self::SUCCESS;
        }

        if ($dryRun) {
            $roleCount = Role::query()
                ->where('guard_name', $guard)
                ->whereHas('permissions', static fn ($q) => $q->where('name', 'sections.dashboard.view'))
                ->count();
            $this->line(sprintf(
                'Would assign all %d widget permission(s) to %d role(s) with sections.dashboard.view.',
                count($definitionNames),
                $roleCount,
            ));

            return self::SUCCESS;
        }

        $widgetPermissions = Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', $definitionNames)
            ->get();

        $roles = Role::query()
            ->where('guard_name', $guard)
            ->whereHas('permissions', static fn ($q) => $q->where('name', 'sections.dashboard.view'))
            ->get();

        foreach ($roles as $role) {
            $role->givePermissionTo($widgetPermissions);
            $this->line("Backfilled widget permissions for role: {$role->name}");
        }

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($roles->isEmpty()) {
            $this->warn('No roles with sections.dashboard.view — nothing to backfill.');
        }

        return self::SUCCESS;
    }
}

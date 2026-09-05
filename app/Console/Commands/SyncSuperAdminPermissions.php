<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class SyncSuperAdminPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:sync-super-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensure the super-admin role has all permissions assigned explicitly.';

    public function handle(): int
    {
        /** @var Role|null $role */
        $role = Role::query()->where('name', 'super-admin')->first();

        if (! $role instanceof Role) {
            $this->error('Role "super-admin" not found.');

            return self::FAILURE;
        }

        $allPermissions = Permission::all();

        if ($allPermissions->isEmpty()) {
            $this->warn('No permissions found to assign.');

            return self::SUCCESS;
        }

        $role->syncPermissions($allPermissions);

        $this->info(sprintf(
            'Synced %d permissions to role "%s".',
            $allPermissions->count(),
            $role->name,
        ));

        return self::SUCCESS;
    }
}

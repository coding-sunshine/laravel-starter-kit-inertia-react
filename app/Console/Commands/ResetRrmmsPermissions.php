<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\Essential\RolesAndPermissionsSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

final class ResetRrmmsPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:reset-rrmms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop all Spatie permissions and re-assign RMMS route-based permissions to roles';

    public function handle(): int
    {
        /** @var array<string, string> $tableNames */
        $tableNames = config('permission.table_names');

        $this->info('Clearing permission cache...');
        // resolve(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->warn('Truncating permission tables (permissions, role_has_permissions, model_has_permissions)...');

        DB::table($tableNames['role_has_permissions'])->truncate();
        DB::table($tableNames['model_has_permissions'])->truncate();
        DB::table($tableNames['permissions'])->truncate();

        $this->info('Rebuilding permissions and reassigning to roles using RolesAndPermissionsSeeder...');

        /** @var RolesAndPermissionsSeeder $seeder */
        $seeder = app(RolesAndPermissionsSeeder::class);
        $seeder->run();

        $this->info('RMMS permissions have been reset and reassigned.');

        return self::SUCCESS;
    }
}

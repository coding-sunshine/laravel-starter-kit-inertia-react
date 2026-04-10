<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SectionPermissionsFormOptions;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Optional: creates the Rake Loader section permission rows from the in-command list.
 * The canonical source is config/section_permissions.php (slug rake_loader);
 * SectionPermissionsSeeder and `php artisan permission:sync-sections` create them from that config.
 * Route names in route_to_permission (e.g. rake-loader.index) map to sections.rake_loader.view — they are not separate DB permissions.
 * Use this command only if you want to sync these names without running a full section sync.
 */
#[AsCommand(name: 'permission:sync-rake-loader')]
final class SyncRakeLoaderSectionPermissionsCommand extends Command
{
    /**
     * Must match sections.rake_loader.* implied by config/section_permissions.php.
     *
     * @var list<string>
     */
    private const array SECTION_PERMISSION_NAMES = [
        'sections.rake_loader.view',
        'sections.rake_loader.update',
    ];

    protected $signature = 'permission:sync-rake-loader
                            {--dry-run : Preview permissions that would be created without applying}
                            {--silent : Suppress output}';

    protected $description = 'Create section permissions for Rake Loader (sections.rake_loader.view|update)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $silent = (bool) $this->option('silent');

        if ($dryRun && ! $silent) {
            $this->warn('DRY RUN - No changes will be made');
        }

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = (string) config('permission.default_guard_name', 'web');

        if (! $silent) {
            $this->line(sprintf('Permissions defined in command: %d', count(self::SECTION_PERMISSION_NAMES)));
        }

        foreach (self::SECTION_PERMISSION_NAMES as $name) {
            if (! $silent) {
                $this->line("+ Ensuring: {$name}");
            }

            if (! $dryRun) {
                Permission::query()->firstOrCreate([
                    'name' => $name,
                    'guard_name' => $guard,
                ]);
            }
        }

        if (! $dryRun) {
            resolve(PermissionRegistrar::class)->forgetCachedPermissions();
            resolve(SectionPermissionsFormOptions::class)->forgetCache();
            resolve(SectionPermissionsFormOptions::class)->forgetOtherCache();
        }

        if (! $silent) {
            $this->info('Rake Loader section permissions sync completed.');
        }

        return self::SUCCESS;
    }
}

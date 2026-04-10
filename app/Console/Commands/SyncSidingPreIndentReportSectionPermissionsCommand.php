<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SectionPermissionsFormOptions;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Creates section permissions for Siding Pre-Indent Report from a dedicated list.
 * Also declared in config/section_permissions.php — keep both in sync.
 */
#[AsCommand(name: 'permission:sync-siding-pre-indent-reports')]
final class SyncSidingPreIndentReportSectionPermissionsCommand extends Command
{
    /**
     * Permission names for this feature (must match config/section_permissions.php slug siding_pre_indent_reports).
     *
     * @var list<string>
     */
    private const array SECTION_PERMISSION_NAMES = [
        'sections.siding_pre_indent_reports.view',
        'sections.siding_pre_indent_reports.create',
        'sections.siding_pre_indent_reports.update',
        'sections.siding_pre_indent_reports.delete',
    ];

    protected $signature = 'permission:sync-siding-pre-indent-reports
                            {--dry-run : Preview permissions that would be created without applying}
                            {--silent : Suppress output}';

    protected $description = 'Create section permissions for Siding Pre-Indent Report (from in-command list)';

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
            $this->info('Siding Pre-Indent Report section permissions sync completed.');
        }

        return self::SUCCESS;
    }
}

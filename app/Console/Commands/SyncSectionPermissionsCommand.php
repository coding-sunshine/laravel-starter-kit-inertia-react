<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SectionPermissionsFormOptions;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'permission:sync-sections')]
final class SyncSectionPermissionsCommand extends Command
{
    protected $signature = 'permission:sync-sections
                            {--dry-run : Preview permissions that would be created without applying}
                            {--silent : Suppress output}';

    protected $description = 'Create missing section permissions from config/section_permissions.php (append-only)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $silent = (bool) $this->option('silent');

        if ($dryRun && ! $silent) {
            $this->warn('DRY RUN - No changes will be made');
        }

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();

        $guard = (string) config('permission.default_guard_name', 'web');
        $targetNames = $this->collectSectionPermissionNames();

        if ($targetNames === []) {
            if (! $silent) {
                $this->info('No section permissions found in config.');
            }

            return self::SUCCESS;
        }

        $existingNames = Permission::query()
            ->where('guard_name', $guard)
            ->where('name', 'like', 'sections.%')
            ->pluck('name')
            ->all();

        $existingSet = array_fill_keys($existingNames, true);

        $toCreate = [];
        foreach ($targetNames as $name) {
            if (! isset($existingSet[$name])) {
                $toCreate[] = $name;
            }
        }

        if (! $silent) {
            $this->line(sprintf('Section permissions in config: %d', count($targetNames)));
            $this->line(sprintf('Missing permissions to create: %d', count($toCreate)));
        }

        foreach ($toCreate as $name) {
            if (! $silent) {
                $this->line("+ Creating: {$name}");
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
            $this->info('Section permission sync completed.');
        }

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function collectSectionPermissionNames(): array
    {
        $names = [];

        $sections = config('section_permissions.sections', []);
        foreach ($sections as $section) {
            $slug = $section['slug'] ?? null;
            $actions = $section['actions'] ?? [];

            if (! is_string($slug) || $actions === []) {
                continue;
            }

            foreach ($actions as $action) {
                if (! is_string($action) || $action === '') {
                    continue;
                }

                $names[] = 'sections.'.$slug.'.'.$action;
            }
        }

        $names = array_values(array_unique($names));
        sort($names);

        return $names;
    }
}

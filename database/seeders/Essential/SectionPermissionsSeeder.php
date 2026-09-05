<?php

declare(strict_types=1);

namespace Database\Seeders\Essential;

use App\Services\SectionPermissionsFormOptions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class SectionPermissionsSeeder extends Seeder
{
    private const string GUARD = 'web';

    /**
     * @return list<string>
     */
    public static function allPermissionNames(): array
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
                $names[] = 'sections.'.$slug.'.'.$action;
            }
        }

        return $names;
    }

    public function run(): void
    {
        resolve(PermissionRegistrar::class)->forgetCachedPermissions();

        $sections = config('section_permissions.sections', []);

        foreach ($sections as $section) {
            $slug = $section['slug'] ?? null;
            $actions = $section['actions'] ?? [];
            if (! is_string($slug) || $actions === []) {
                continue;
            }
            foreach ($actions as $action) {
                $name = 'sections.'.$slug.'.'.$action;
                Permission::query()->firstOrCreate(
                    ['name' => $name, 'guard_name' => self::GUARD],
                );
            }
        }

        resolve(PermissionRegistrar::class)->forgetCachedPermissions();
        resolve(SectionPermissionsFormOptions::class)->forgetCache();
        resolve(SectionPermissionsFormOptions::class)->forgetOtherCache();
    }
}

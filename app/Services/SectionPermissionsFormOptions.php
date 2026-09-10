<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

/**
 * Builds section-based permission options for the Filament Role form.
 * Returns sections with permission id => action label (e.g. "View", "Create") for checkboxes.
 */
final class SectionPermissionsFormOptions
{
    private const ACTION_LABELS = [
        'view' => 'View',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'upload' => 'Upload',
        'manage' => 'Manage',
        'generate' => 'Generate',
    ];

    /**
     * Sections with options for the role form. Only includes section permissions that exist in DB.
     *
     * @return array<int, array{slug: string, label: string, options: array<int, string>}>
     */
    public function getSections(): array
    {
        return Cache::remember(
            'section_permissions_form_options',
            now()->addDay(),
            function (): array {
                return $this->buildSections();
            }
        );
    }

    public function forgetCache(): void
    {
        Cache::forget('section_permissions_form_options');
    }

    /**
     * Other (non-section) permissions for the role form: id => name.
     *
     * @return array<int, string>
     */
    public function getOtherPermissions(): array
    {
        return Cache::remember(
            'section_permissions_form_other',
            now()->addDay(),
            function (): array {
                return Permission::query()
                    ->where('name', 'not like', 'sections.%')
                    ->where('name', 'not like', 'dashboard.widgets.%')
                    ->orderBy('name')
                    ->get()
                    ->mapWithKeys(fn (Permission $p): array => [$p->id => $p->name])
                    ->all();
            }
        );
    }

    public function forgetOtherCache(): void
    {
        Cache::forget('section_permissions_form_other');
    }

    /**
     * @return array<int, array{slug: string, label: string, options: array<int, string>}>
     */
    private function buildSections(): array
    {
        $sectionConfigs = config('section_permissions.sections', []);
        if ($sectionConfigs === []) {
            return [];
        }

        $permissionsByName = Permission::query()
            ->where('name', 'like', 'sections.%')
            ->get()
            ->keyBy('name');

        $result = [];
        foreach ($sectionConfigs as $section) {
            $slug = $section['slug'] ?? null;
            $label = $section['label'] ?? $slug;
            $actions = $section['actions'] ?? [];
            if (! is_string($slug) || $actions === []) {
                continue;
            }

            $options = [];
            foreach ($actions as $action) {
                $name = 'sections.'.$slug.'.'.$action;
                $perm = $permissionsByName->get($name);
                if ($perm !== null) {
                    $options[$perm->id] = self::ACTION_LABELS[$action] ?? ucfirst($action);
                }
            }

            if ($options !== []) {
                $result[] = [
                    'slug' => $slug,
                    'label' => $label,
                    'options' => $options,
                ];
            }
        }

        return $result;
    }
}

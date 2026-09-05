<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Schemas;

use App\Services\PermissionCategoryResolver;
use App\Services\SectionPermissionsFormOptions;
use App\Support\Dashboard\DashboardWidgetPermissions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Arr;

final class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        $optionsService = resolve(SectionPermissionsFormOptions::class);
        $sections = $optionsService->getSections();

        $components = [
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->alphaDash(),
            Select::make('guard_name')
                ->options([
                    'web' => 'Web',
                    'api' => 'API',
                ])
                ->default('web')
                ->required(),
        ];

        if ($sections !== []) {
            $hasDashboardSection = false;

            foreach ($sections as $section) {
                $label = $section['label'];
                $slug = $section['slug'];
                $opts = $section['options'];
                $fieldName = 'permissions_section_'.$slug;

                if ($slug === 'dashboard') {
                    $hasDashboardSection = true;
                }

                $sectionSchema = [
                    CheckboxList::make($fieldName)
                        ->options($opts)
                        ->bulkToggleable()
                        ->columns(4)
                        ->gridDirection('row'),
                ];

                if ($slug === 'dashboard') {
                    $widgetGroups = self::makeDashboardWidgetGroupComponents();
                    if ($widgetGroups !== null) {
                        $sectionSchema = array_merge($sectionSchema, $widgetGroups);
                    }
                }

                $section = Section::make($label)
                    ->schema($sectionSchema)
                    ->collapsible();

                if ($slug === 'dashboard') {
                    $section->columnSpanFull();
                }

                $components[] = $section;
            }

            if (! $hasDashboardSection) {
                self::appendDashboardWidgetsSection($components);
            }

            $other = $optionsService->getOtherPermissions();
            if ($other !== []) {
                $components[] = Section::make(__('Other'))
                    ->schema([
                        CheckboxList::make('permissions_other')
                            ->options($other)
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(2)
                            ->gridDirection('column'),
                    ])
                    ->collapsed();
            }
        } else {
            $resolver = resolve(PermissionCategoryResolver::class);
            $grouped = $resolver->getPermissionsGroupedByCategory();
            $categories = config('permission_categories.categories', []);

            foreach ($grouped as $categoryKey => $opts) {
                $label = $categoryKey === 'other'
                    ? __('Other')
                    : ($categories[$categoryKey]['description'] ?? $categoryKey);
                $fieldName = 'permissions_'.$categoryKey;

                $components[] = Section::make($label)
                    ->schema([
                        CheckboxList::make($fieldName)
                            ->options($opts)
                            ->searchable()
                            ->bulkToggleable()
                            ->gridDirection('column')
                            ->columns(2),
                    ])
                    ->collapsible();
            }

            self::appendDashboardWidgetsSection($components);
        }

        return $schema->components($components);
    }

    /**
     * Merge all permissions_* form fields into a single list of permission IDs.
     *
     * @param  array<string, mixed>  $data
     * @return array<int>
     */
    public static function mergePermissionIds(array $data): array
    {
        $merged = [];
        foreach (array_keys($data) as $key) {
            if (str_starts_with($key, 'permissions_') && is_array($data[$key] ?? null)) {
                $merged = array_merge($merged, Arr::flatten($data[$key]));
            }
        }

        return array_values(array_unique(array_filter($merged, is_numeric(...))));
    }

    /**
     * Return form state keys used for permissions (for EditRole to fill from record).
     *
     * @return array<string, array<int>>
     */
    public static function getPermissionStateFromRecord(\Spatie\Permission\Models\Role $record): array
    {
        $optionsService = resolve(SectionPermissionsFormOptions::class);
        $sections = $optionsService->getSections();
        $rolePermissionIds = $record->permissions->pluck('id')->all();
        $state = [];

        if ($sections !== []) {
            foreach ($sections as $section) {
                $slug = $section['slug'];
                $options = $section['options'];
                $fieldName = 'permissions_section_'.$slug;
                $idsInSection = array_keys($options);
                $state[$fieldName] = array_values(array_intersect($rolePermissionIds, $idsInSection));
            }

            $other = $optionsService->getOtherPermissions();
            if ($other !== []) {
                $otherIds = array_keys($other);
                $state['permissions_other'] = array_values(array_intersect($rolePermissionIds, $otherIds));
            }
        } else {
            $resolver = resolve(PermissionCategoryResolver::class);
            $grouped = $resolver->getPermissionsGroupedByCategory();
            foreach ($grouped as $categoryKey => $options) {
                $fieldName = 'permissions_'.$categoryKey;
                $state[$fieldName] = array_values(array_intersect($rolePermissionIds, array_keys($options)));
            }
        }

        foreach (DashboardWidgetPermissions::filamentWidgetGroups() as $groupKey => $_groupMeta) {
            $options = DashboardWidgetPermissions::filamentCheckboxOptionsForGroup($groupKey);
            if ($options === []) {
                continue;
            }
            $fieldName = DashboardWidgetPermissions::formFieldNameForWidgetGroup($groupKey);
            $idsInGroup = array_keys($options);
            $state[$fieldName] = array_values(array_intersect($rolePermissionIds, $idsInGroup));
        }

        return $state;
    }

    /**
     * @param  array<mixed>  $components
     */
    private static function appendDashboardWidgetsSection(array &$components): void
    {
        $widgetGroups = self::makeDashboardWidgetGroupComponents();
        if ($widgetGroups === null) {
            return;
        }

        $components[] = Section::make(__('Dashboard widgets'))
            ->description(__('Granular blocks on the management dashboard. The role still needs Dashboard → View to open the dashboard.'))
            ->schema($widgetGroups)
            ->columnSpanFull()
            ->collapsible();
    }

    /**
     * @return list<Fieldset>|null
     */
    private static function makeDashboardWidgetGroupComponents(): ?array
    {
        $components = [];
        foreach (DashboardWidgetPermissions::filamentWidgetGroups() as $groupKey => $groupMeta) {
            $options = DashboardWidgetPermissions::filamentCheckboxOptionsForGroup($groupKey);
            if ($options === []) {
                continue;
            }

            $components[] = Fieldset::make($groupMeta['label'])
                ->columns(1)
                ->schema([
                    CheckboxList::make(DashboardWidgetPermissions::formFieldNameForWidgetGroup($groupKey))
                        ->hiddenLabel()
                        ->options($options)
                        ->bulkToggleable()
                        ->columns(2)
                        ->gridDirection('column'),
                ]);
        }

        return $components === [] ? null : $components;
    }
}

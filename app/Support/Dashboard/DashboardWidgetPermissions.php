<?php

declare(strict_types=1);

namespace App\Support\Dashboard;

use App\Models\User;
use Spatie\Permission\Models\Permission;

/**
 * Dashboard block permissions (Filament "Dashboard widgets" section). Not in section_permissions.php.
 *
 * @see \App\Console\Commands\SyncDashboardWidgetPermissionsCommand (`permission:sync-dashboard-widgets`)
 */
final class DashboardWidgetPermissions
{
    /**
     * @return array<string, string> permission_name => Filament label
     */
    public static function definitions(): array
    {
        return [
            'dashboard.widgets.executive_tables_road_dispatch' => 'Executive — Road dispatch (table)',
            'dashboard.widgets.executive_tables_rail_dispatch' => 'Executive — Rail dispatch (table)',
            'dashboard.widgets.executive_tables_production' => 'Executive — Production OB/Coal (tables)',
            'dashboard.widgets.executive_tables_custom' => 'Executive — Custom range (table)',
            'dashboard.widgets.executive_tables_fy_summary' => 'Executive — FY summary (table)',
            'dashboard.widgets.executive_chart_road_dispatch' => 'Executive — Road dispatch (chart)',
            'dashboard.widgets.executive_chart_rail_dispatch' => 'Executive — Rail dispatch (chart)',
            'dashboard.widgets.executive_chart_production' => 'Executive — Production (donut)',
            'dashboard.widgets.executive_chart_penalty_by_siding' => 'Executive — Penalty by siding',
            'dashboard.widgets.executive_chart_powerplant_dispatch' => 'Executive — Power plant dispatch (chart)',
            'dashboard.widgets.executive_chart_fy' => 'Executive — FY Production & Dispatch charts',
            'dashboard.widgets.siding_overview_performance' => 'Siding overview — Performance',
            'dashboard.widgets.siding_overview_penalty_trend' => 'Siding overview — Penalty trend',
            'dashboard.widgets.siding_overview_power_plant_distribution' => 'Siding overview — Power plant distribution',
            'dashboard.widgets.operations_coal_transport' => 'Operations — Coal transport report',
            'dashboard.widgets.operations_daily_rake_details' => 'Operations — Daily rake details',
            'dashboard.widgets.operations_truck_receipt_trend' => 'Operations — Truck receipt trend',
            'dashboard.widgets.operations_shift_vehicle_receipt' => 'Operations — Shift-wise vehicle receipt',
            'dashboard.widgets.operations_live_rake_status' => 'Operations — Live rake status',
            'dashboard.widgets.penalty_control_type_distribution' => 'Penalty control — Type distribution',
            'dashboard.widgets.penalty_control_yesterday_predicted' => 'Penalty control — Yesterday predicted',
            'dashboard.widgets.penalty_control_penalty_by_siding' => 'Penalty control — Penalty by siding',
            'dashboard.widgets.penalty_control_applied_vs_rr' => 'Penalty control — Applied vs RR',
            'dashboard.widgets.rake_performance' => 'Rake-wise performance',
            'dashboard.widgets.loader_overload_trends' => 'Loader overload trends',
            'dashboard.widgets.power_plant_dispatch_section' => 'Power plant wise dispatch',
            'dashboard.widgets.global_coal_stock_strip' => 'Global — Coal stock strip',
            'dashboard.widgets.global_kpi_sidebar' => 'Global — KPI sidebar',
            'penalty_exposure_command' => 'Command Center — Penalty exposure',
            'rake_pipeline_command' => 'Command Center — Rake pipeline',
            'siding_risk_score' => 'Command Center — Siding risk score',
            'alert_feed_command' => 'Command Center — Alert feed',
            'dispatch_summary_command' => 'Command Center — Dispatch summary',
            'operator_rake_command' => 'Command Center — Operator rake',
        ];
    }

    /**
     * @return list<string>
     */
    public static function executiveWidgetNames(): array
    {
        return [
            'dashboard.widgets.executive_tables_road_dispatch',
            'dashboard.widgets.executive_tables_rail_dispatch',
            'dashboard.widgets.executive_tables_production',
            'dashboard.widgets.executive_tables_custom',
            'dashboard.widgets.executive_tables_fy_summary',
            'dashboard.widgets.executive_chart_road_dispatch',
            'dashboard.widgets.executive_chart_rail_dispatch',
            'dashboard.widgets.executive_chart_production',
            'dashboard.widgets.executive_chart_penalty_by_siding',
            'dashboard.widgets.executive_chart_powerplant_dispatch',
            'dashboard.widgets.executive_chart_fy',
            'penalty_exposure_command',
            'rake_pipeline_command',
            'siding_risk_score',
            'alert_feed_command',
            'dispatch_summary_command',
        ];
    }

    public static function userHasAnyExecutiveWidget(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->can('bypass-permissions')) {
            return true;
        }

        foreach (self::executiveWidgetNames() as $name) {
            if ($user->hasPermissionTo($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public static function allowedNamesForUser(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        if ($user->can('bypass-permissions')) {
            return array_keys(self::definitions());
        }

        $allowed = [];
        foreach (array_keys(self::definitions()) as $name) {
            if ($user->hasPermissionTo($name)) {
                $allowed[] = $name;
            }
        }

        return $allowed;
    }

    /**
     * Main dashboard tab IDs in UI order (must match resources/js/pages/dashboard.tsx `DASHBOARD_SECTIONS`).
     *
     * @return list<string>
     */
    public static function orderedDashboardSectionIds(): array
    {
        return [
            'executive-overview',
            'siding-overview',
            'operations',
            'penalty-control',
            'rake-performance',
            'loader-overload',
            'power-plant',
        ];
    }

    public static function userCanSeeDashboardSection(?User $user, string $sectionId): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->can('bypass-permissions')) {
            return true;
        }

        return match ($sectionId) {
            'executive-overview' => self::userHasAnyExecutiveWidget($user),
            'siding-overview' => $user->hasPermissionTo('dashboard.widgets.siding_overview_performance')
                || $user->hasPermissionTo('dashboard.widgets.siding_overview_penalty_trend')
                || $user->hasPermissionTo('dashboard.widgets.siding_overview_power_plant_distribution'),
            'operations' => $user->hasPermissionTo('dashboard.widgets.operations_coal_transport')
                || $user->hasPermissionTo('dashboard.widgets.operations_daily_rake_details')
                || $user->hasPermissionTo('dashboard.widgets.operations_truck_receipt_trend')
                || $user->hasPermissionTo('dashboard.widgets.operations_shift_vehicle_receipt')
                || $user->hasPermissionTo('dashboard.widgets.operations_live_rake_status'),
            'penalty-control' => $user->hasPermissionTo('dashboard.widgets.penalty_control_type_distribution')
                || $user->hasPermissionTo('dashboard.widgets.penalty_control_yesterday_predicted')
                || $user->hasPermissionTo('dashboard.widgets.penalty_control_penalty_by_siding')
                || $user->hasPermissionTo('dashboard.widgets.penalty_control_applied_vs_rr'),
            'rake-performance' => $user->hasPermissionTo('dashboard.widgets.rake_performance'),
            'loader-overload' => $user->hasPermissionTo('dashboard.widgets.loader_overload_trends'),
            'power-plant' => $user->hasPermissionTo('dashboard.widgets.power_plant_dispatch_section'),
            default => false,
        };
    }

    /**
     * @return list<string>
     */
    public static function permittedDashboardSectionIdsForUser(?User $user): array
    {
        $ids = [];
        foreach (self::orderedDashboardSectionIds() as $sectionId) {
            if (self::userCanSeeDashboardSection($user, $sectionId)) {
                $ids[] = $sectionId;
            }
        }

        return $ids;
    }

    /**
     * Filament role form: widget groups (fieldset heading + checklists).
     *
     * @return array<string, array{label: string, names: list<string>}>
     */
    public static function filamentWidgetGroups(): array
    {
        return [
            'executive' => [
                'label' => __('Executive'),
                'names' => self::executiveWidgetNames(),
            ],
            'siding_overview' => [
                'label' => __('Siding overview'),
                'names' => [
                    'dashboard.widgets.siding_overview_performance',
                    'dashboard.widgets.siding_overview_penalty_trend',
                    'dashboard.widgets.siding_overview_power_plant_distribution',
                ],
            ],
            'operations' => [
                'label' => __('Operations'),
                'names' => [
                    'dashboard.widgets.operations_coal_transport',
                    'dashboard.widgets.operations_daily_rake_details',
                    'dashboard.widgets.operations_truck_receipt_trend',
                    'dashboard.widgets.operations_shift_vehicle_receipt',
                    'dashboard.widgets.operations_live_rake_status',
                ],
            ],
            'penalty_control' => [
                'label' => __('Penalty control'),
                'names' => [
                    'dashboard.widgets.penalty_control_type_distribution',
                    'dashboard.widgets.penalty_control_yesterday_predicted',
                    'dashboard.widgets.penalty_control_penalty_by_siding',
                    'dashboard.widgets.penalty_control_applied_vs_rr',
                ],
            ],
            'rake_performance' => [
                'label' => __('Rake-wise performance'),
                'names' => ['dashboard.widgets.rake_performance'],
            ],
            'loader_overload' => [
                'label' => __('Loader overload'),
                'names' => ['dashboard.widgets.loader_overload_trends'],
            ],
            'power_plant' => [
                'label' => __('Power plant wise dispatch'),
                'names' => ['dashboard.widgets.power_plant_dispatch_section'],
            ],
            'global' => [
                'label' => __('Global'),
                'names' => [
                    'dashboard.widgets.global_coal_stock_strip',
                    'dashboard.widgets.global_kpi_sidebar',
                ],
            ],
            'command_center' => [
                'label' => __('Command Center'),
                'names' => [
                    'penalty_exposure_command',
                    'rake_pipeline_command',
                    'siding_risk_score',
                    'alert_feed_command',
                    'dispatch_summary_command',
                    'operator_rake_command',
                ],
            ],
        ];
    }

    public static function formFieldNameForWidgetGroup(string $groupKey): string
    {
        return 'permissions_dashboard_widgets_'.$groupKey;
    }

    /**
     * Checkbox options for one group: permission id => label (DB-backed, definition order).
     *
     * @return array<int, string>
     */
    public static function filamentCheckboxOptionsForGroup(string $groupKey): array
    {
        $groups = self::filamentWidgetGroups();
        if (! isset($groups[$groupKey]['names'])) {
            return [];
        }

        $definitions = self::definitions();
        $names = $groups[$groupKey]['names'];
        if ($names === []) {
            return [];
        }

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $names)
            ->get(['id', 'name']);

        $byName = $permissions->keyBy('name');

        $options = [];
        foreach ($names as $name) {
            $permission = $byName->get($name);
            if ($permission === null) {
                continue;
            }
            $label = $definitions[$name] ?? $permission->name;
            $options[(int) $permission->id] = $label;
        }

        return $options;
    }

    /**
     * Checkbox options: permission id => label (only permissions that exist in DB).
     *
     * @return array<int, string>
     */
    public static function filamentCheckboxOptions(): array
    {
        $merged = [];
        foreach (array_keys(self::filamentWidgetGroups()) as $groupKey) {
            foreach (self::filamentCheckboxOptionsForGroup($groupKey) as $id => $label) {
                $merged[$id] = $label;
            }
        }

        return $merged;
    }
}

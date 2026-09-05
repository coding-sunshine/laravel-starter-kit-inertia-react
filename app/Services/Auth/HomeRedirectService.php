<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;

final class HomeRedirectService
{
    /**
     * Routes to try after login, in order. Keep in sync with
     * `resources/js/components/app-sidebar.tsx` (platform nav).
     *
     * @var list<string>
     */
    private const HOME_ROUTE_ORDER = [
        'dashboard',
        'master-data.power-plants.index',
        'master-data.sidings.index',
        'master-data.loaders.index',
        'master-data.penalty-types.index',
        'master-data.section-timers.index',
        'master-data.shift-timings.index',
        'master-data.opening-coal-stock.index',
        'master-data.daily-stock-details.index',
        'master-data.master-data.distance-matrix.index',
        'billing.index',
        'production.coal.index',
        'production.ob.index',
        'vehicle-workorders.index',
        'reports.index',
        'historical.mines.index',
        'historical.railway-siding.index',
        'indents.index',
        'rakes.index',
        'weighments.index',
        'rake-loader.index',
        'railway-receipts.index',
        'road-dispatch.daily-vehicle-entries.index',
        'railway-siding-empty-weighment.index',
        'vehicle-dispatch.index',
    ];

    /**
     * Optional OR permissions per route (otherwise uses `section_permissions.route_to_permission` only).
     *
     * @var array<string, list<string>>
     */
    private const HOME_ROUTE_PERMISSION_OVERRIDES = [
        'railway-receipts.index' => [
            'sections.railway_receipts.view',
            'sections.railway_receipts.upload',
        ],
    ];

    /**
     * Determine the home route name for the given user based on their permissions.
     */
    public function getHomeRouteFor(User $user): string
    {
        if ($user->can('bypass-permissions')) {
            return 'dashboard';
        }

        /** @var array<string, string> $routeToPermission */
        $routeToPermission = config('section_permissions.route_to_permission', []);

        foreach (self::HOME_ROUTE_ORDER as $routeName) {
            if ($routeName === '') {
                continue;
            }

            $permissions = self::HOME_ROUTE_PERMISSION_OVERRIDES[$routeName] ?? null;
            if ($permissions === null) {
                $single = $routeToPermission[$routeName] ?? null;
                $permissions = is_string($single) && $single !== '' ? [$single] : [];
            }

            foreach ($permissions as $permission) {
                if (! is_string($permission) || $permission === '') {
                    continue;
                }
                if ($user->can($permission)) {
                    return $routeName;
                }
            }
        }

        return 'dashboard';
    }
}

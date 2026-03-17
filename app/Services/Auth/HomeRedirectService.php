<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;

final class HomeRedirectService
{
    /**
     * Determine the home route name for the given user based on their permissions.
     */
    public function getHomeRouteFor(User $user): string
    {
        // Respect intended URL when available; fallback is decided here.
        if ($user->can('bypass-permissions')) {
            return 'dashboard';
        }

        $navPermissions = config('section_permissions.nav_permission', []);

        $candidates = [
            ['key' => 'dashboard', 'route' => 'dashboard'],
            ['key' => 'rakes', 'route' => 'rakes.index'],
            ['key' => 'indents', 'route' => 'indents.index'],
            ['key' => 'railway_siding_record_data', 'route' => 'road-dispatch.daily-vehicle-entries.index'],
            ['key' => 'railway_siding_empty_weighment', 'route' => 'railway-siding-empty-weighment.index'],
            ['key' => 'weighments', 'route' => 'weighments.index'],
            ['key' => 'mines_dispatch_data', 'route' => 'vehicle-dispatch.index'],
            ['key' => 'historical_mines', 'route' => 'historical.mines.index'],
            ['key' => 'historical_railway_siding', 'route' => 'historical.railway-siding.index'],
            ['key' => 'reports', 'route' => 'reports.index'],
        ];

        foreach ($candidates as $candidate) {
            $permissionKey = $candidate['key'];
            $routeName = $candidate['route'];

            $permission = $navPermissions[$permissionKey] ?? null;
            if (! is_string($permission) || $permission === '') {
                continue;
            }

            if ($user->can($permission)) {
                return $routeName;
            }
        }

        return 'dashboard';
    }
}

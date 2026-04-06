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
        if ($user->can('bypass-permissions')) {
            return 'dashboard';
        }

        /** @var array<string, string> $routeToPermission */
        $routeToPermission = config('section_permissions.route_to_permission', []);

        $candidates = [
            // Prefer task-entry pages when user has upload-only permissions.
            ['route' => 'railway-receipts.index'],
            ['route' => 'weighments.index'],
            ['route' => 'vehicle-dispatch.index'],
            ['route' => 'road-dispatch.daily-vehicle-entries.index'],
            ['route' => 'railway-siding-empty-weighment.index'],
            ['route' => 'rakes.index'],
            ['route' => 'indents.index'],
            ['route' => 'reports.index'],
            ['route' => 'historical.mines.index'],
            ['route' => 'historical.railway-siding.index'],
            ['route' => 'rake-loader.index'],
            ['route' => 'dashboard'],
        ];

        foreach ($candidates as $candidate) {
            $routeName = $candidate['route'];
            $requiredPermission = $routeToPermission[$routeName] ?? null;
            if (! is_string($requiredPermission) || $requiredPermission === '') {
                continue;
            }

            if ($user->can($requiredPermission)) {
                return $routeName;
            }
        }

        return 'dashboard';
    }
}

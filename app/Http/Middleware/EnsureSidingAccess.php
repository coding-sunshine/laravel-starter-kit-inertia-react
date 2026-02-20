<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure user has access to requested siding
 *
 * Usage in routes:
 *   Route::get('/sidings/{siding}/rakes', ...)->middleware('siding.access');
 *
 * Checks that the authenticated user can access the siding
 * extracted from the route parameter 'siding'
 */
final class EnsureSidingAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Allow if not authenticated (will be handled by auth middleware)
        if (! $user) {
            return $next($request);
        }

        // Super admin can access all sidings
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Get siding from route parameter
        $siding = $request->route('siding');

        if (! $siding) {
            // No siding in route, allow to proceed
            return $next($request);
        }

        // Check if user can access this siding
        abort_unless($user->canAccessSiding($siding->id), 403, 'You do not have access to this siding.');

        return $next($request);
    }
}

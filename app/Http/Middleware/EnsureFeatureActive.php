<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abort with 404 when the given feature is inactive for the authenticated user.
 * Guests are allowed through (no user = no feature check).
 */
final class EnsureFeatureActive
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $featureMap = config('feature-flags.route_feature_map', []);
        $featureClass = $featureMap[$featureKey] ?? null;

        if (! $featureClass || ! class_exists($featureClass)) {
            return $next($request);
        }

        if (! Feature::for($user)->active($featureClass)) {
            abort(404);
        }

        return $next($request);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCountryAllowed
{
    /**
     * Check billing country restrictions before checkout using laravel-geo-genius.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('billing.geo_restriction_enabled', false)) {
            return $next($request);
        }

        if (! function_exists('laravelGeoGenius')) {
            return $next($request);
        }

        $geo = laravelGeoGenius()->geo()->locateVisitor();
        $countryCode = mb_strtoupper((string) ($geo['countryCode'] ?? ''));

        if ($countryCode === '') {
            return $next($request);
        }

        $blocked = config('billing.geo_blocked_countries', []);
        $allowed = config('billing.geo_allowed_countries', []);

        if (! empty($allowed) && ! in_array($countryCode, $allowed, true)) {
            return to_route('dashboard')
                ->with('error', __('Your country is not supported for billing.'));
        }

        if (! empty($blocked) && in_array($countryCode, $blocked, true)) {
            return to_route('dashboard')
                ->with('error', __('Billing is not available in your country.'));
        }

        return $next($request);
    }
}

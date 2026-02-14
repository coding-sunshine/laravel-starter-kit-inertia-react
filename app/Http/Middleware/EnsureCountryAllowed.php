<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCountryAllowed
{
    /**
     * Check billing country restrictions before checkout.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // TODO: Implement country restriction check when country_restrictions table and config exist.
        return $next($request);
    }
}

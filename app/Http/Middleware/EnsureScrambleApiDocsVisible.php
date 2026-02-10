<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Features\ScrambleApiDocsFeature;
use Closure;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;

/**
 * For requests to /docs/api or /docs/api.json, abort with 404 when
 * ScrambleApiDocsFeature is inactive for the authenticated user.
 * Guests are allowed through.
 */
final class EnsureScrambleApiDocsVisible
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! str_starts_with($request->path(), 'docs/api')) {
            return $next($request);
        }

        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! Feature::for($user)->active(ScrambleApiDocsFeature::class)) {
            abort(404);
        }

        return $next($request);
    }
}

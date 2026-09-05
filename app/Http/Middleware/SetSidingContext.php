<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Siding;
use App\Models\User;
use App\Services\SidingContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Initialize and maintain siding context for authenticated users.
 * Web: session or default siding. API: X-Siding-ID header.
 */
final class SetSidingContext
{
    public const string SIDING_HEADER = 'X-Siding-ID';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            SidingContext::forget();

            return $next($request);
        }

        /** @var User $user */
        $user = Auth::user();

        // Already set and valid — skip
        if (SidingContext::check() && $user->canAccessSiding(SidingContext::id())) {
            return $next($request);
        }

        // API: check header
        $headerSidingId = $this->getSidingIdFromHeader($request);
        if ($headerSidingId !== null) {
            if (! $user->canAccessSiding($headerSidingId)) {
                return response()->json([
                    'message' => __('You do not have access to the specified siding.'),
                    'error' => 'invalid_siding',
                ], 403);
            }

            $siding = Siding::query()->find($headerSidingId);
            if ($siding instanceof Siding) {
                SidingContext::set($siding);

                return $next($request);
            }
        }

        SidingContext::initForUser($user);

        return $next($request);
    }

    private function getSidingIdFromHeader(Request $request): ?int
    {
        $headerValue = $request->header(self::SIDING_HEADER);

        if ($headerValue === null || $headerValue === '') {
            return null;
        }

        if (is_numeric($headerValue)) {
            return (int) $headerValue;
        }

        return null;
    }
}

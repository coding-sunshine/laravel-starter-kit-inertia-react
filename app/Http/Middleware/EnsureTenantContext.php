<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure an active tenant (organization) context exists. Abort 403 if not.
 * Re-initializes from session/default so tenant is set before route model binding.
 */
final class EnsureTenantContext
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! TenantContext::check() && Auth::check()) {
            TenantContext::initFromSession();
            if (! TenantContext::check()) {
                $user = Auth::user();
                if ($user instanceof User) {
                    TenantContext::initForUser($user);
                }
            }
        }

        if (! TenantContext::check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Organization context is required.',
                    'error' => 'missing_organization_context',
                ], 403);
            }

            return to_route('dashboard');
        }

        return $next($request);
    }
}

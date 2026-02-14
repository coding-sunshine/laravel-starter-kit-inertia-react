<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Models\User;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Initialize and maintain tenant (organization) context for authenticated users.
 * Web: session or default org. API: X-Organization-ID header.
 */
final class SetTenantContext
{
    public const string ORGANIZATION_HEADER = 'X-Organization-ID';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            TenantContext::forget();

            return $next($request);
        }

        /** @var User $user */
        $user = Auth::user();

        if (TenantContext::check() && $user->belongsToOrganization(TenantContext::id())) {
            return $next($request);
        }

        $headerOrgId = $this->getOrganizationIdFromHeader($request);

        if ($headerOrgId !== null) {
            if (! $user->belongsToOrganization($headerOrgId)) {
                return response()->json([
                    'message' => __('You do not have access to the specified :term.', [
                        'term' => mb_strtolower(__((string) config('tenancy.term', 'organization'))),
                    ]),
                    'error' => 'invalid_organization',
                ], 403);
            }

            $organization = Organization::query()->find($headerOrgId);
            if ($organization instanceof Organization) {
                TenantContext::set($organization);

                return $next($request);
            }
        }

        TenantContext::initForUser($user);

        return $next($request);
    }

    private function getOrganizationIdFromHeader(Request $request): ?int
    {
        $headerValue = $request->header(self::ORGANIZATION_HEADER);

        if ($headerValue === null || $headerValue === '') {
            return null;
        }

        if (is_numeric($headerValue)) {
            return (int) $headerValue;
        }

        $organization = Organization::query()->where('slug', $headerValue)->first();

        return $organization?->id;
    }
}

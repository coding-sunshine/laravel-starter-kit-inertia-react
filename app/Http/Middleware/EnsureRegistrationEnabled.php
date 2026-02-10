<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Settings\AuthSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirect guests to login when registration is disabled (AuthSettings).
 */
final class EnsureRegistrationEnabled
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app(AuthSettings::class)->registration_enabled) {
            return $next($request);
        }

        return redirect()->route('login')
            ->with('message', __('Registration is currently disabled.'));
    }
}

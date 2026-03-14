<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

final class RedirectSettingsPages
{
    private const REDIRECT_PATHS = [
        'settings/password',
        'settings/appearance',
        'settings/personal-data-export',
        'settings/two-factor',
        'settings/achievements',
        'onboarding',
    ];

    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $path = mb_trim($request->path(), '/');

        foreach (self::REDIRECT_PATHS as $redirectPath) {
            if ($path === $redirectPath || str_starts_with($path, $redirectPath.'/')) {
                return redirect(URL::route('dashboard'));
            }
        }

        return $next($request);
    }
}

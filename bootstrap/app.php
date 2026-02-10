<?php

declare(strict_types=1);

use AlizHarb\ActivityLog\Http\Middleware\ActivityLogContextMiddleware;
use App\Http\Middleware\AdditionalSecurityHeaders;
use App\Http\Middleware\AutoPermissionMiddleware;
use App\Http\Middleware\EnforceIpWhitelist;
use App\Http\Middleware\EnsureFeatureActive;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\EnsureRegistrationEnabled;
use App\Http\Middleware\EnsureScrambleApiDocsVisible;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ServeFavicon;
use App\Http\Middleware\ThrottleTwoFactorManagement;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Spatie\Csp\AddCspHeaders;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Spatie\ResponseCache\Middlewares\CacheResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);
        $middleware->statefulApi();

        $middleware->alias([
            'feature' => EnsureFeatureActive::class,
            'registration.enabled' => EnsureRegistrationEnabled::class,
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'auto.permission' => AutoPermissionMiddleware::class,
            'ip.whitelist' => EnforceIpWhitelist::class,
        ]);

        $webAppend = [
            EnsureScrambleApiDocsVisible::class,
            AddCspHeaders::class,
            AdditionalSecurityHeaders::class,
            ActivityLogContextMiddleware::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            CacheResponse::class,
            AutoPermissionMiddleware::class,
            ThrottleTwoFactorManagement::class,
            EnsureOnboardingComplete::class,
        ];

        $middleware->web(
            append: $webAppend,
            prepend: [
                ServeFavicon::class,
            ],
        );

        $middleware->api(append: [
            AddCspHeaders::class,
            AdditionalSecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

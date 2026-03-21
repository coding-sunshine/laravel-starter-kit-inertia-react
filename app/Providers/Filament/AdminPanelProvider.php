<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use A909M\FilamentStateFusion\FilamentStateFusionPlugin;
use AlizHarb\ActivityLog\ActivityLogPlugin;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Permissions\PermissionResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Stephenjude\FilamentFeatureFlag\FeatureFlagPlugin;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->authGuard('web')
            ->login()
            ->brandName('RMMS')
            ->brandLogo(asset('logo.png'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('logo.png'))
            ->font('Inter Variable', null, null, [])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->globalSearch()
            ->darkMode()
            ->maxContentWidth(Width::SevenExtraLarge)
            ->databaseNotifications()
            ->plugins([
                FilamentStateFusionPlugin::make(),
                FeatureFlagPlugin::make(),
                ActivityLogPlugin::make()
                    ->label('Log')
                    ->pluralLabel('Logs')
                    ->navigationGroup('System'),
            ])
            ->resources([
                UserResource::class,
                RoleResource::class,
                PermissionResource::class,
            ])
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

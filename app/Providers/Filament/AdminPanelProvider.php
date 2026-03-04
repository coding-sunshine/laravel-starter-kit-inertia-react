<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use A909M\FilamentStateFusion\FilamentStateFusionPlugin;
use AlizHarb\ActivityLog\ActivityLogPlugin;
use App\Http\Middleware\EnsureSetupComplete;
use App\Http\Middleware\SetTenantContext;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
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
            ->brandName(config('app.name'))
            ->brandLogo(null)
            ->favicon(asset('favicon.svg'))
            ->font('Inter Variable', null, null, [])
            ->colors([
                'primary' => Color::Slate,
            ])
            ->globalSearch()
            ->defaultThemeMode(ThemeMode::Light)
            ->darkMode(false)
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->maxContentWidth(Width::SevenExtraLarge)
            ->databaseNotifications()
            ->navigationGroups([
                NavigationGroup::make('CRM')
                    ->icon('heroicon-o-building-office'),
                NavigationGroup::make('Properties')
                    ->icon('heroicon-o-home'),
                NavigationGroup::make('Sales & Reservations')
                    ->icon('heroicon-o-currency-dollar'),
                NavigationGroup::make('Tasks & Marketing')
                    ->icon('heroicon-o-megaphone'),
                NavigationGroup::make('AI Bot')
                    ->icon('heroicon-o-cpu-chip')
                    ->collapsed(),
                NavigationGroup::make('Analytics')
                    ->icon('heroicon-o-chart-pie')
                    ->collapsed(),
                NavigationGroup::make('Reports')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsed(),
                NavigationGroup::make('System')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
            ])
            ->plugins([
                FilamentStateFusionPlugin::make(),
                FeatureFlagPlugin::make(),
                ActivityLogPlugin::make()
                    ->label('Log')
                    ->pluralLabel('Logs')
                    ->navigationGroup('System')
                    ->navigationSort(110),
            ])
            ->resources([
                // Core CRM
                \App\Filament\Resources\Contacts\ContactResource::class,
                \App\Filament\Resources\Notes\NoteResource::class,
                \App\Filament\Resources\Relationships\RelationshipResource::class,
                \App\Filament\Resources\Partners\PartnerResource::class,
                // Properties
                \App\Filament\Resources\Projects\ProjectResource::class,
                \App\Filament\Resources\Lots\LotResource::class,
                \App\Filament\Resources\Developers\DeveloperResource::class,
                \App\Filament\Resources\ProjectUpdates\ProjectUpdateResource::class,
                // Sales & Reservations
                \App\Filament\Resources\Sales\SaleResource::class,
                \App\Filament\Resources\PropertyReservations\PropertyReservationResource::class,
                \App\Filament\Resources\PropertyEnquiries\PropertyEnquiryResource::class,
                \App\Filament\Resources\PropertySearches\PropertySearchResource::class,
                \App\Filament\Resources\Commissions\CommissionResource::class,
                // Tasks & Marketing
                \App\Filament\Resources\Tasks\TaskResource::class,
                \App\Filament\Resources\MailLists\MailListResource::class,
                // AI Bot
                \App\Filament\Resources\AiBot\AiBotCategoryResource::class,
                \App\Filament\Resources\AiBot\AiBotPromptCommandResource::class,
                \App\Filament\Resources\AiBot\AiBotBoxResource::class,
                \App\Filament\Resources\BrochureProcessings\BrochureProcessingResource::class,
                // System
                \App\Filament\Resources\Users\UserResource::class,
                \App\Filament\Resources\Organizations\OrganizationResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
                \App\Filament\Pages\ProductAnalytics::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                SetTenantContext::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureSetupComplete::class,
            ]);
    }
}

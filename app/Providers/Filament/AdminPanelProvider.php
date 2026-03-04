<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use A909M\FilamentStateFusion\FilamentStateFusionPlugin;
use AlizHarb\ActivityLog\ActivityLogPlugin;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\EnsureSetupComplete;
use App\Http\Middleware\SetTenantContext;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
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
                NavigationGroup::make('Accounts')
                    ->icon('heroicon-o-user-group')
                    ->collapsible(),
                NavigationGroup::make('Marketing Tools')
                    ->icon('heroicon-o-briefcase')
                    ->collapsible(),
                NavigationGroup::make('Property Portal')
                    ->icon('heroicon-o-building-office-2')
                    ->collapsible(),
                NavigationGroup::make('Reports')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsible(),
                NavigationGroup::make('Online Forms')
                    ->icon('heroicon-o-document-text')
                    ->collapsible(),
                NavigationGroup::make('Bot Management')
                    ->icon('heroicon-o-cpu-chip')
                    ->collapsible(),
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
                // Accounts
                \App\Filament\Resources\Contacts\ContactResource::class,
                \App\Filament\Resources\Partners\PartnerResource::class, // Affiliates
                \App\Filament\Resources\Users\UserResource::class, // Subscribers
                \App\Filament\Resources\Developers\DeveloperResource::class,
                \App\Filament\Resources\BDMs\BDMResource::class,
                \App\Filament\Resources\SalesAgents\SalesAgentResource::class,
                \App\Filament\Resources\ReferralPartners\ReferralPartnerResource::class,
                \App\Filament\Resources\PIABAdmins\PIABAdminResource::class,
                \App\Filament\Resources\PropertyManagers\PropertyManagerResource::class,

                // Marketing Tools
                \App\Filament\Resources\Websites\WebsiteResource::class,
                \App\Filament\Resources\Flyers\FlyerResource::class, // Landing Page
                \App\Filament\Resources\FlyerTemplates\FlyerTemplateResource::class, // Brochures

                // Property Portal
                \App\Filament\Resources\Projects\ProjectResource::class,
                \App\Filament\Resources\Lots\LotResource::class,
                \App\Filament\Resources\Favourites\FavouritesResource::class,
                \App\Filament\Resources\Featured\FeaturedResource::class,
                \App\Filament\Resources\PotentialProperties\PotentialPropertyResource::class, // Potential Properties

                // Sales (standalone)
                \App\Filament\Resources\Sales\SaleResource::class,

                // Reports
                \App\Filament\Resources\NetworkActivities\NetworkActivityResource::class, // Network Activity (sort 1)
                \App\Filament\Resources\Notes\NoteResource::class, // Notes History (sort 2)
                \App\Filament\Resources\LogInHistories\LogInHistoryResource::class, // Log In History (sort 3)
                \App\Filament\Resources\SameDeviceDetections\SameDeviceDetectionResource::class, // Same Device Detection (sort 4)
                \App\Filament\Resources\PropertyReservations\PropertyReservationResource::class, // Reservations (sort 5)
                \App\Filament\Resources\Tasks\TaskResource::class, // Tasks (sort 6)
                \App\Filament\Resources\SprRequests\SprRequestResource::class, // SPR History (sort 7)
                \App\Filament\Resources\Reports\ReportsWebsiteResource::class, // Website (sort 8)
                \App\Filament\Resources\WordpressWebsites\WordpressWebsiteResource::class, // WordPress Website (sort 9)
                \App\Filament\Resources\Reports\ReportsLandingPageResource::class, // Landing Page (sort 10)
                \App\Filament\Resources\ApprovedApiKeys\ApprovedApiKeysResource::class, // Approved API Keys (sort 11)

                // Online Forms
                \App\Filament\Resources\PropertyEnquiries\PropertyEnquiryResource::class, // Property Enquiry
                \App\Filament\Resources\PropertySearches\PropertySearchResource::class, // Property Search Request
                \App\Filament\Resources\FinanceAssessments\FinanceAssessmentResource::class, // Finance Assessment

                // Bot Management
                \App\Filament\Resources\AiBot\AiBotBoxResource::class,
                \App\Filament\Resources\AiBot\AiBotCategoryResource::class,
                \App\Filament\Resources\BrochureProcessings\BrochureProcessingResource::class,

                // Standalone items
                \App\Filament\Resources\MailLists\MailListResource::class,

                // System
                \App\Filament\Resources\Organizations\OrganizationResource::class,
            ])
            ->pages([
                Dashboard::class,
                \App\Filament\Pages\BulkDocumentProcessing::class,
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

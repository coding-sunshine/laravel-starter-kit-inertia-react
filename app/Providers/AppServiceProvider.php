<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\OrganizationMemberAdded;
use App\Events\OrganizationMemberRemoved;
use App\Events\User\UserCreated;
use App\Listeners\Billing\AddCreditsFromLemonSqueezyOrder;
use App\Listeners\Billing\SyncSubscriptionSeatsOnMemberChange;
use App\Listeners\CreatePersonalOrganizationOnUserCreated;
use App\Listeners\Gamification\GrantGamificationOnUserCreated;
use App\Listeners\LogImpersonationEvents;
use App\Listeners\MigrationListener;
use App\Listeners\SendSlackAlertOnJobFailed;
use App\Models\Shareable;
use App\Models\User;
use App\Observers\ActivityLogObserver;
use App\Observers\PermissionActivityObserver;
use App\Observers\RoleActivityObserver;
use App\Observers\UserObserver;
use App\Policies\ShareablePolicy;
use App\Services\PaymentGateway\PaymentGatewayManager;
use App\Services\PrismService;
use App\Settings\SeoSettings;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use LemonSqueezy\Laravel\Events\OrderCreated;
use Machour\DataTable\Http\Controllers\DataTableExportController;
use Pan\PanConfiguration;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use STS\FilamentImpersonate\Events\EnterImpersonation;
use STS\FilamentImpersonate\Events\LeaveImpersonation;
use Throwable;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }

        $this->app->singleton(PrismService::class, fn (): PrismService => new PrismService);

        if (class_exists(\Essa\APIToolKit\Exceptions\Handler::class)) {
            $this->app->singleton(ExceptionHandler::class, \Essa\APIToolKit\Exceptions\Handler::class);
        }

        $this->app->singleton(PaymentGatewayManager::class);

        config(['filament-impersonate.redirect_to' => '/dashboard']);
    }

    public function boot(): void
    {
        $this->configurePan();
        $this->registerFleetMorphMap();
        $this->registerFleetModelBindings();

        $this->registerSeoViewComposer();
        $this->registerActivityLogTaps();

        Gate::policy(Shareable::class, ShareablePolicy::class);

        Gate::before(function ($user, string $ability, array $arguments): ?bool {
            if (! $user) {
                return null;
            }
            if (! $this->userHasBypassPermissions($user)) {
                return null;
            }
            if ($this->isUserModelDangerousOperation($ability, $arguments)) {
                return null;
            }

            return true;
        });

        if (config('seeding.auto_sync_after_migrations', true)) {
            Event::listen(MigrationsEnded::class, MigrationListener::class);
        }

        Event::listen(EnterImpersonation::class, [LogImpersonationEvents::class, 'handleEnterImpersonation']);
        Event::listen(LeaveImpersonation::class, [LogImpersonationEvents::class, 'handleLeaveImpersonation']);
        Event::listen(JobFailed::class, SendSlackAlertOnJobFailed::class);
        Event::listen(UserCreated::class, GrantGamificationOnUserCreated::class);
        Event::listen(UserCreated::class, CreatePersonalOrganizationOnUserCreated::class);
        Event::listen(OrganizationMemberAdded::class, SyncSubscriptionSeatsOnMemberChange::class);
        Event::listen(OrganizationMemberRemoved::class, SyncSubscriptionSeatsOnMemberChange::class);
        Event::listen(OrderCreated::class, AddCreditsFromLemonSqueezyOrder::class);
        User::observe(UserObserver::class);

        DataTableExportController::register('users', \App\DataTables\UserDataTable::class);
    }

    private function userHasBypassPermissions(object $user): bool
    {
        return (bool) DB::table('model_has_permissions')
            ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
            ->where('permissions.name', 'bypass-permissions')
            ->where('model_has_permissions.model_id', $user->getKey())
            ->where('model_has_permissions.model_type', $user::class)
            ->exists()
            || DB::table('model_has_roles')
                ->join('role_has_permissions', 'model_has_roles.role_id', '=', 'role_has_permissions.role_id')
                ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
                ->where('permissions.name', 'bypass-permissions')
                ->where('model_has_roles.model_id', $user->getKey())
                ->where('model_has_roles.model_type', $user::class)
                ->exists();
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    private function isUserModelDangerousOperation(string $ability, array $arguments): bool
    {
        if (! in_array($ability, ['delete', 'forceDelete'], true)) {
            return false;
        }
        $model = $arguments[0] ?? null;

        return $model instanceof User;
    }

    private function registerFleetMorphMap(): void
    {
        $existing = Relation::morphMap() ?: [];
        Relation::morphMap(array_merge($existing, [
            'vehicle' => \App\Models\Fleet\Vehicle::class,
            'driver' => \App\Models\Fleet\Driver::class,
            'organization' => \App\Models\Organization::class,
            'trailer' => \App\Models\Fleet\Trailer::class,
        ]));
    }

    /**
     * Resolve fleet models by id without organization scope so policy returns 403 (not 404) when org mismatches.
     */
    private function registerFleetModelBindings(): void
    {
        $scope = \App\Models\Scopes\OrganizationScope::class;
        $bindings = [
            'location' => \App\Models\Fleet\Location::class,
            'cost_center' => \App\Models\Fleet\CostCenter::class,
            'driver' => \App\Models\Fleet\Driver::class,
            'trailer' => \App\Models\Fleet\Trailer::class,
            'vehicle' => \App\Models\Fleet\Vehicle::class,
            'geofence' => \App\Models\Fleet\Geofence::class,
            'garage' => \App\Models\Fleet\Garage::class,
            'fuel_station' => \App\Models\Fleet\FuelStation::class,
            'ev_charging_station' => \App\Models\Fleet\EvChargingStation::class,
            'operator_licence' => \App\Models\Fleet\OperatorLicence::class,
            'route' => \App\Models\Fleet\Route::class,
            'trip' => \App\Models\Fleet\Trip::class,
            'behavior_event' => \App\Models\Fleet\BehaviorEvent::class,
            'telematics_device' => \App\Models\Fleet\TelematicsDevice::class,
            'geofence_event' => \App\Models\Fleet\GeofenceEvent::class,
            'fuel_card' => \App\Models\Fleet\FuelCard::class,
            'fuel_transaction' => \App\Models\Fleet\FuelTransaction::class,
            'service_schedule' => \App\Models\Fleet\ServiceSchedule::class,
            'work_order' => \App\Models\Fleet\WorkOrder::class,
            'defect' => \App\Models\Fleet\Defect::class,
            'compliance_item' => \App\Models\Fleet\ComplianceItem::class,
            'driver_working_time' => \App\Models\Fleet\DriverWorkingTime::class,
            'tachograph_download' => \App\Models\Fleet\TachographDownload::class,
        ];
        foreach ($bindings as $key => $modelClass) {
            Route::bind($key, function (string $value) use ($modelClass, $scope) {
                return $modelClass::withoutGlobalScope($scope)->findOrFail($value);
            });
        }
    }

    private function configurePan(): void
    {
        PanConfiguration::allowedAnalytics([
            'settings-nav-profile',
            'settings-nav-password',
            'settings-nav-two-factor',
            'settings-nav-appearance',
            'settings-nav-data-export',
            'settings-nav-achievements',
            'settings-nav-onboarding',
            'appearance-tab-light',
            'appearance-tab-dark',
            'appearance-tab-system',
            'auth-login-button',
            'auth-sign-up-link',
            'auth-register-button',
            'auth-log-in-link',
            'auth-forgot-password-button',
            'welcome-dashboard',
            'welcome-log-in',
            'welcome-register',
            'welcome-blog',
            'welcome-changelog',
            'welcome-help',
            'welcome-contact',
            'nav-dashboard',
            'nav-organizations',
            'nav-billing',
            'nav-blog',
            'nav-changelog',
            'nav-help',
            'nav-contact',
            'nav-api-docs',
            'nav-repository',
            'nav-documentation',
            'dashboard-quick-edit-profile',
            'dashboard-quick-settings',
            'dashboard-quick-export-pdf',
            'dashboard-quick-contact',
            'dashboard-quick-email-templates',
            'dashboard-quick-product-analytics',
            'dashboard-card-view-analytics',
            'command-palette',
            'nav-chat',
            'nav-users',
            'chat-conversation-list',
            'chat-new-conversation',
            'chat-delete-conversation',
            'chat-rename-conversation',
            'chat-copy-message',
            'chat-copy-code',
            'chat-mobile-menu',
            'chat-send-message',
            'dashboard-chart',
            'users-table',
            'pages-index',
            'pages-create',
            'pages-edit-preview',
            'pages-edit-save',
            'pages-duplicate',
            'pages-delete',
        ]);
    }

    private function registerSeoViewComposer(): void
    {
        View::composer('app', function ($view): void {
            try {
                $settings = resolve(SeoSettings::class);
                $seo = [
                    'meta_title' => $settings->meta_title ?: config('app.name'),
                    'meta_description' => $settings->meta_description ?? '',
                    'og_image' => $settings->og_image,
                    'app_url' => mb_rtrim(config('app.url'), '/'),
                ];
            } catch (Throwable) {
                $seo = [
                    'meta_title' => config('app.name'),
                    'meta_description' => '',
                    'og_image' => null,
                    'app_url' => mb_rtrim(config('app.url'), '/'),
                ];
            }
            $seo['current_url'] = request()->url();
            $view->with('seo', $seo);
        });
    }

    private function registerActivityLogTaps(): void
    {
        try {
            if (Schema::hasTable(config('activitylog.table_name', 'activity_log'))) {
                $activityModel = ActivitylogServiceProvider::determineActivityModel();
                $activityModel::observe(ActivityLogObserver::class);
            }
        } catch (Throwable) {
            // DB may be unavailable (e.g. docs:sync --check in pre-commit, CI without DB)
        }

        Role::observe(RoleActivityObserver::class);
        Permission::observe(PermissionActivityObserver::class);
    }
}

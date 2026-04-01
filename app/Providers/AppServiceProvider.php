<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\DemurrageThresholdCrossed;
use App\Events\OrganizationMemberAdded;
use App\Events\OrganizationMemberRemoved;
use App\Events\User\UserCreated;
use App\Http\Controllers\Filament\RedirectAdminHomeController;
use App\Http\Responses\Filament\LoginResponse as FilamentLoginResponse;
use App\Listeners\Billing\AddCreditsFromLemonSqueezyOrder;
use App\Listeners\Billing\SyncSubscriptionSeatsOnMemberChange;
use App\Listeners\CreatePersonalOrganizationOnUserCreated;
use App\Listeners\Gamification\GrantGamificationOnUserCreated;
use App\Listeners\LogImpersonationEvents;
use App\Listeners\MigrationListener;
use App\Listeners\SendDemurrageEscalation;
use App\Listeners\SendSlackAlertOnJobFailed;
use App\Models\Shareable;
use App\Models\User;
use App\Observers\ActivityLogObserver;
use App\Observers\PenaltyObserver;
use App\Observers\PermissionActivityObserver;
use App\Observers\RoleActivityObserver;
use App\Observers\UserObserver;
use App\Policies\ShareablePolicy;
use App\Services\PaymentGateway\PaymentGatewayManager;
use App\Services\PrismService;
use App\Settings\SeoSettings;
use Essa\APIToolKit\Exceptions\Handler as ApiToolKitExceptionHandler;
use Filament\Auth\Http\Responses\Contracts\LoginResponse as FilamentLoginResponseContract;
use Filament\Http\Controllers\RedirectToHomeController;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use LemonSqueezy\Laravel\Events\OrderCreated;
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
        $this->app->singleton(PrismService::class, fn (): PrismService => new PrismService);

        $this->app->singleton(ExceptionHandler::class, ApiToolKitExceptionHandler::class);

        $this->app->singleton(PaymentGatewayManager::class);

        config(['filament-impersonate.redirect_to' => '/dashboard']);

        $this->app->bind(FilamentLoginResponseContract::class, FilamentLoginResponse::class);

        $this->app->bind(RedirectToHomeController::class, RedirectAdminHomeController::class);
    }

    public function boot(): void
    {
        $this->configurePan();

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
        Event::listen(DemurrageThresholdCrossed::class, SendDemurrageEscalation::class);
        User::observe(UserObserver::class);
        \App\Models\Penalty::observe(PenaltyObserver::class);
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
            'nav-rake-loader',
            'dashboard-quick-edit-profile',
            'dashboard-quick-settings',
            'dashboard-quick-export-pdf',
            'dashboard-quick-contact',
            'dashboard-quick-email-templates',
            'dashboard-quick-product-analytics',
            'dashboard-card-view-analytics',
            'chat-open',
            'chat-new-chat',
            'chat-send',
            'chat-conversation-item',
            'chat-suggested-question',
            'penalty-assign-responsibility',
            'penalty-set-root-cause',
            'penalty-analytics-tab',
            'penalty-index-charts',
            'penalty-drill-down',
            'report-select-type',
            'report-generate',
            'report-download-csv',
            'report-download-xlsx',
            'report-siding-coal-column-sources',
            'siding-switcher',
            'penalty-ask-ai',
            'penalty-root-cause-drill',
            'penalty-dispute-drill',
            'penalty-cost-savings',
            'vehicle-dispatch-tab-main-data',
            'vehicle-dispatch-tab-dpr',
            'vehicle-dispatch-generate-dpr',
            'dashboard-coal-transport-export-xlsx',
            'vehicle-dispatch-coal-transport-export',
            'nav-railway-siding-empty-weighment',
            'nav-shift-timings',
            'shift-timings-edit',
            'nav-opening-coal-stock',
            'opening-coal-stock-edit',
            'opening-coal-stock-fix',
            'nav-vehicle-workorders',
            'vehicle-workorders-filters',
            'vehicle-workorders-table',
            'vehicle-workorder-edit',
            'vehicle-workorder-update',
            'rr-details-tabs',
            'rr-tab-overview',
            'rr-tab-wagons',
            'rr-tab-charges',
            'rr-tab-penalties',
            'rr-tab-raw',
            'rr-upload-pdf-button',
            'weighments-upload-pdf-button',
            'weighments-upload-first-document',
            'weighments-upload-dialog-cancel',
            'weighments-upload-with-rake-button',
            'rake-rr-upload-pdf-button',
            'rake-rr-upload-primary-pdf-button',
            'rake-rr-diverted-mode-checkbox',
            'rake-rr-diversion-destination-add',
            'rake-rr-diversion-destination-remove',
            'rake-rr-upload-diversion-pdf-button',
            'indents-upload-pdf-button',
            'indents-create-button',
            'indents-create-submit',
            'indents-create-cancel',
            'indents-edit-submit',
            'indents-edit-cancel',
            'indents-edit-download-pdf',
            'nav-production-coal',
            'nav-production-ob',
            'production-add-entry',
            'production-edit-entry',
            'production-delete-entry',
            'shift-lock-overlay',
            'daily-vehicle-entries-table-fullscreen',
            'daily-vehicle-entries-add-five-rows-pack',
            'daily-vehicle-entries-hourly-record',
            'daily-vehicle-entries-hourly-record-export',
            'railway-empty-weighment-table-fullscreen',
            'railway-empty-weighment-add-five-rows-pack',
            'railway-empty-weighment-hourly-record',
            'historical-mines-filter-apply',
            'historical-mines-filter-clear',
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

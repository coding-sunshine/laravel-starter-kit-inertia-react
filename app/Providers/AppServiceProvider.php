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
            'location' => \App\Models\Fleet\Location::class,
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
            'emissions_record' => \App\Models\Fleet\EmissionsRecord::class,
            'carbon_target' => \App\Models\Fleet\CarbonTarget::class,
            'sustainability_goal' => \App\Models\Fleet\SustainabilityGoal::class,
            'ev_charging_session' => \App\Models\Fleet\EvChargingSession::class,
            'ev_battery_data' => \App\Models\Fleet\EvBatteryData::class,
            'training_course' => \App\Models\Fleet\TrainingCourse::class,
            'training_session' => \App\Models\Fleet\TrainingSession::class,
            'driver_qualification' => \App\Models\Fleet\DriverQualification::class,
            'training_enrollment' => \App\Models\Fleet\TrainingEnrollment::class,
            'cost_allocation' => \App\Models\Fleet\CostAllocation::class,
            'alert' => \App\Models\Fleet\Alert::class,
            'alert_preference' => \App\Models\Fleet\AlertPreference::class,
            'report' => \App\Models\Fleet\Report::class,
            'report_execution' => \App\Models\Fleet\ReportExecution::class,
            'api_integration' => \App\Models\Fleet\ApiIntegration::class,
            'workshop_bay' => \App\Models\Fleet\WorkshopBay::class,
            'parts_inventory' => \App\Models\Fleet\PartsInventory::class,
            'parts_supplier' => \App\Models\Fleet\PartsSupplier::class,
            'tyre_inventory' => \App\Models\Fleet\TyreInventory::class,
            'grey_fleet_vehicle' => \App\Models\Fleet\GreyFleetVehicle::class,
            'mileage_claim' => \App\Models\Fleet\MileageClaim::class,
            'pool_vehicle_booking' => \App\Models\Fleet\PoolVehicleBooking::class,
            'contractor' => \App\Models\Fleet\Contractor::class,
            'contractor_compliance' => \App\Models\Fleet\ContractorCompliance::class,
            'contractor_invoice' => \App\Models\Fleet\ContractorInvoice::class,
            'driver_wellness_record' => \App\Models\Fleet\DriverWellnessRecord::class,
            'driver_coaching_plan' => \App\Models\Fleet\DriverCoachingPlan::class,
            'vehicle_check_template' => \App\Models\Fleet\VehicleCheckTemplate::class,
            'vehicle_check' => \App\Models\Fleet\VehicleCheck::class,
            'vehicle_check_item' => \App\Models\Fleet\VehicleCheckItem::class,
            'risk_assessment' => \App\Models\Fleet\RiskAssessment::class,
            'vehicle_disc' => \App\Models\Fleet\VehicleDisc::class,
            'tachograph_calibration' => \App\Models\Fleet\TachographCalibration::class,
            'safety_policy_acknowledgment' => \App\Models\Fleet\SafetyPolicyAcknowledgment::class,
            'permit_to_work' => \App\Models\Fleet\PermitToWork::class,
            'ppe_assignment' => \App\Models\Fleet\PpeAssignment::class,
            'safety_observation' => \App\Models\Fleet\SafetyObservation::class,
            'toolbox_talk' => \App\Models\Fleet\ToolboxTalk::class,
            'fine' => \App\Models\Fleet\Fine::class,
            'vehicle_lease' => \App\Models\Fleet\VehicleLease::class,
            'vehicle_recall' => \App\Models\Fleet\VehicleRecall::class,
            'warranty_claim' => \App\Models\Fleet\WarrantyClaim::class,
            'parking_allocation' => \App\Models\Fleet\ParkingAllocation::class,
            'e_lock_event' => \App\Models\Fleet\ElockEvent::class,
            'axle_load_reading' => \App\Models\Fleet\AxleLoadReading::class,
        ];
        foreach ($bindings as $key => $modelClass) {
            Route::bind($key, function (string $value) use ($modelClass, $scope) {
                return $modelClass::withoutGlobalScope($scope)->findOrFail($value);
            });
        }
        Route::bind('api_log', fn (string $value) => \App\Models\Fleet\ApiLog::findOrFail($value));
        Route::bind('data_migration_run', fn (string $value) => \App\Models\Fleet\DataMigrationRun::findOrFail($value));
        Route::bind('dashcam_clip', fn (string $value) => \App\Models\Fleet\DashcamClip::withoutGlobalScope($scope)->findOrFail($value));
        Route::bind('vehicle_tyre', fn (string $value) => \App\Models\Fleet\VehicleTyre::findOrFail($value));
        Route::bind('vehicle_check_item', fn (string $value) => \App\Models\Fleet\VehicleCheckItem::findOrFail($value));
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

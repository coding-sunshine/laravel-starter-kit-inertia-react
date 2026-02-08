<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\LogImpersonationEvents;
use App\Listeners\MigrationListener;
use App\Models\User;
use App\Observers\ActivityLogObserver;
use App\Observers\PermissionActivityObserver;
use App\Observers\RoleActivityObserver;
use App\Services\PrismService;
use Essa\APIToolKit\Exceptions\Handler as ApiToolKitExceptionHandler;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Lab404\Impersonate\Events\LeaveImpersonation;
use Lab404\Impersonate\Events\TakeImpersonation;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PrismService::class, fn (): PrismService => new PrismService);

        $this->app->singleton(ExceptionHandler::class, ApiToolKitExceptionHandler::class);

        config(['filament-impersonate.redirect_to' => '/dashboard']);
    }

    public function boot(): void
    {
        $this->registerActivityLogTaps();

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

        Event::listen(TakeImpersonation::class, [LogImpersonationEvents::class, 'handleTakeImpersonation']);
        Event::listen(LeaveImpersonation::class, [LogImpersonationEvents::class, 'handleLeaveImpersonation']);
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

    private function registerActivityLogTaps(): void
    {
        if (Schema::hasTable(config('activitylog.table_name', 'activity_log'))) {
            $activityModel = ActivitylogServiceProvider::determineActivityModel();
            $activityModel::observe(ActivityLogObserver::class);
        }

        Role::observe(RoleActivityObserver::class);
        Permission::observe(PermissionActivityObserver::class);
    }
}

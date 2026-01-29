<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\MigrationListener;
use App\Services\PrismService;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PrismService::class, fn (): PrismService => new PrismService);
    }

    public function boot(): void
    {
        Gate::before(fn ($user, $ability): ?bool => $user?->hasRole('super-admin') ? true : null);

        if (config('seeding.auto_sync_after_migrations', true)) {
            Event::listen(MigrationsEnded::class, MigrationListener::class);
        }
    }
}

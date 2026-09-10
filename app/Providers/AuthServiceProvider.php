<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Indent;
use App\Models\Rake;
use App\Policies\IndentPolicy;
use App\Policies\RakePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Rake::class => RakePolicy::class,
        Indent::class => IndentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}

<?php

declare(strict_types=1);

namespace App\Providers;

use Faker\Generator;
use Illuminate\Support\ServiceProvider;

final class FakerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->extend(Generator::class, function (Generator $faker): Generator {
            // Add custom Faker providers here
            // Example: $faker->addProvider(new CustomProvider($faker));

            return $faker;
        });
    }
}

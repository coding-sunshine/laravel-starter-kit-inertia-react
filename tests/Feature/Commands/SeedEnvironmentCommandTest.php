<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

it('can seed with default categories', function (): void {
    Artisan::call('seed:environment');

    expect(User::query()->count())->toBeGreaterThan(0);
});

it('can seed specific category', function (): void {
    Artisan::call('seed:environment', ['--category' => 'development']);

    expect(User::query()->count())->toBeGreaterThan(0);
});

it('requires force flag in production', function (): void {
    app()->detectEnvironment(fn () => 'production');

    $exitCode = Artisan::call('seed:environment');

    expect($exitCode)->toBe(1);
});

it('can run with fresh option', function (): void {
    if (config('database.default') === 'sqlite' && config('database.connections.sqlite.database') === ':memory:') {
        $this->markTestSkipped('SQLite :memory: cannot VACUUM inside a transaction (RefreshDatabase).');
    }

    User::factory()->create();

    Artisan::call('seed:environment', ['--fresh' => true]);

    expect(User::query()->count())->toBeGreaterThan(0);
});

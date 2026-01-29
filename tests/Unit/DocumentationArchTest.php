<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $manifestPath = base_path('docs/.manifest.json');
    if (! File::exists($manifestPath)) {
        $this->markTestSkipped('docs/.manifest.json is required for documentation arch tests.');
    }
});

it('documentation is complete (no undocumented actions, controllers, or pages)', function (): void {
    $exitCode = Artisan::call('docs:sync', ['--check' => true]);

    expect($exitCode)->toBe(0);
});

// Run `php artisan docs:review` manually or in CI for signature/quality checks.

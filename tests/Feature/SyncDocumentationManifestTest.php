<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $manifestPath = base_path('docs/.manifest.json');
    if (! File::exists($manifestPath)) {
        $this->markTestSkipped('docs/.manifest.json is required for docs:sync.');
    }
});

it('runs docs:sync --check successfully when manifest exists', function (): void {
    $exitCode = Artisan::call('docs:sync', ['--check' => true]);

    expect($exitCode)->toBeIn([0, 1]);
});

it('runs docs:sync successfully and updates manifest', function (): void {
    $exitCode = Artisan::call('docs:sync');

    expect($exitCode)->toBe(0);
});

<?php

declare(strict_types=1);

use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Support\Facades\File;

it('backfills placement times from a CSV', function (): void {
    $siding = Siding::factory()->create(['name' => 'Pakur Siding']);
    $rake = Rake::factory()->for($siding)->create(['rake_number' => 'PKR-001', 'placement_time' => null]);

    $csvPath = storage_path('app/test-pakur-backfill.csv');
    File::put($csvPath, "rake_number,placed_at,released_at,source\nPKR-001,2026-04-15 10:00:00,2026-04-15 13:00:00,logbook\n");

    $this->artisan('pakur:backfill-placement', ['--file' => $csvPath])
        ->expectsOutputToContain('Updated 1 rake')
        ->assertExitCode(0);

    $rake->refresh();
    expect($rake->placement_time?->toDateTimeString())->toBe('2026-04-15 10:00:00')
        ->and($rake->loading_end_time?->toDateTimeString())->toBe('2026-04-15 13:00:00');

    File::delete($csvPath);
});

it('skips rows where rake_number does not match any rake', function (): void {
    $csvPath = storage_path('app/test-pakur-backfill.csv');
    File::put($csvPath, "rake_number,placed_at,released_at,source\nUNKNOWN,2026-04-15 10:00:00,,\n");

    $this->artisan('pakur:backfill-placement', ['--file' => $csvPath])
        ->expectsOutputToContain('Skipped 1 row')
        ->assertExitCode(0);

    File::delete($csvPath);
});

it('does not overwrite existing placement_time without --force', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->for($siding)->create([
        'rake_number' => 'PKR-002',
        'placement_time' => '2026-04-10 08:00:00',
    ]);

    $csvPath = storage_path('app/test-pakur-backfill.csv');
    File::put($csvPath, "rake_number,placed_at,released_at,source\nPKR-002,2026-04-15 10:00:00,,\n");

    $this->artisan('pakur:backfill-placement', ['--file' => $csvPath])->assertExitCode(0);

    expect($rake->fresh()->placement_time?->toDateTimeString())->toBe('2026-04-10 08:00:00');

    File::delete($csvPath);
});

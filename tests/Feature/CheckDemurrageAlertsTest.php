<?php

declare(strict_types=1);

use App\Events\DemurrageThresholdCrossed;
use App\Models\Alert;
use App\Models\Organization;
use App\Models\Rake;
use App\Models\Siding;
use Database\Seeders\Essential\RakeManagementRolePermissionSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->seed(RakeManagementRolePermissionSeeder::class);

    $org = Organization::factory()->create();
    $this->siding = Siding::query()->create([
        'organization_id' => $org->id,
        'name' => 'Test Siding',
        'code' => 'TST',
        'location' => 'Test',
        'station_code' => 'TST',
        'is_active' => true,
    ]);
});

test('fires event when rake crosses 60 minute threshold', function (): void {
    Event::fake([DemurrageThresholdCrossed::class]);

    Rake::query()->create([
        'siding_id' => $this->siding->id,
        'rake_number' => 'DEM-001',
        'state' => 'loading',
        'wagon_count' => 10,
        'loading_start_time' => now()->subMinutes(130),
        'free_time_minutes' => 180,
        'loaded_weight_mt' => 3500,
    ]);

    $this->artisan('rrmcs:check-demurrage')->assertSuccessful();

    Event::assertDispatched(DemurrageThresholdCrossed::class, fn (DemurrageThresholdCrossed $event): bool => $event->threshold === 'demurrage_60');
});

test('fires event when rake crosses 0 minute threshold (free time exceeded)', function (): void {
    Event::fake([DemurrageThresholdCrossed::class]);

    Rake::query()->create([
        'siding_id' => $this->siding->id,
        'rake_number' => 'DEM-002',
        'state' => 'loading',
        'wagon_count' => 10,
        'loading_start_time' => now()->subMinutes(200),
        'free_time_minutes' => 180,
        'loaded_weight_mt' => 3500,
    ]);

    $this->artisan('rrmcs:check-demurrage')->assertSuccessful();

    Event::assertDispatched(DemurrageThresholdCrossed::class, fn (DemurrageThresholdCrossed $event): bool => $event->threshold === 'demurrage_0'
        && $event->remainingMinutes === 0
        && $event->projectedPenalty > 0);
});

test('does not fire event for rakes with plenty of free time remaining', function (): void {
    Event::fake([DemurrageThresholdCrossed::class]);

    Rake::query()->create([
        'siding_id' => $this->siding->id,
        'rake_number' => 'DEM-003',
        'state' => 'loading',
        'wagon_count' => 10,
        'loading_start_time' => now()->subMinutes(10),
        'free_time_minutes' => 180,
        'loaded_weight_mt' => 3500,
    ]);

    $this->artisan('rrmcs:check-demurrage')->assertSuccessful();

    Event::assertNotDispatched(DemurrageThresholdCrossed::class);
});

test('does not fire duplicate event within cache TTL', function (): void {
    Event::fake([DemurrageThresholdCrossed::class]);

    $rake = Rake::query()->create([
        'siding_id' => $this->siding->id,
        'rake_number' => 'DEM-004',
        'state' => 'loading',
        'wagon_count' => 10,
        'loading_start_time' => now()->subMinutes(130),
        'free_time_minutes' => 180,
        'loaded_weight_mt' => 3500,
    ]);

    // Simulate a prior run by placing the cache key
    Cache::put("demurrage:{$rake->id}:demurrage_60", true, now()->addHour());

    $this->artisan('rrmcs:check-demurrage')->assertSuccessful();

    Event::assertNotDispatched(DemurrageThresholdCrossed::class);
});

test('ignores rakes not in loading state', function (): void {
    Event::fake([DemurrageThresholdCrossed::class]);

    Rake::query()->create([
        'siding_id' => $this->siding->id,
        'rake_number' => 'DEM-005',
        'state' => 'dispatched',
        'wagon_count' => 10,
        'loading_start_time' => now()->subMinutes(200),
        'free_time_minutes' => 180,
        'loaded_weight_mt' => 3500,
    ]);

    $this->artisan('rrmcs:check-demurrage')->assertSuccessful();

    Event::assertNotDispatched(DemurrageThresholdCrossed::class);
});

test('syncs in-app alerts via SyncDemurrageAlertsAction', function (): void {
    Event::fake([DemurrageThresholdCrossed::class]);

    Rake::query()->create([
        'siding_id' => $this->siding->id,
        'rake_number' => 'DEM-006',
        'state' => 'loading',
        'wagon_count' => 10,
        'loading_start_time' => now()->subMinutes(200),
        'free_time_minutes' => 180,
        'loaded_weight_mt' => 3500,
    ]);

    $this->artisan('rrmcs:check-demurrage')->assertSuccessful();

    expect(Alert::query()->where('rake_id', '!=')->count())->toBeGreaterThanOrEqual(1);
});

test('fires multiple threshold events for a single deeply overdue rake', function (): void {
    Event::fake([DemurrageThresholdCrossed::class]);

    Rake::query()->create([
        'siding_id' => $this->siding->id,
        'rake_number' => 'DEM-007',
        'state' => 'loading',
        'wagon_count' => 10,
        'loading_start_time' => now()->subMinutes(200),
        'free_time_minutes' => 180,
        'loaded_weight_mt' => 3500,
    ]);

    $this->artisan('rrmcs:check-demurrage')->assertSuccessful();

    // A rake 20 min overdue has crossed all three thresholds: 60, 30, and 0
    Event::assertDispatched(DemurrageThresholdCrossed::class, 3);
});

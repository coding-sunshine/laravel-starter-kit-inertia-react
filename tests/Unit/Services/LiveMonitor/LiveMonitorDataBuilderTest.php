<?php

declare(strict_types=1);

use App\Enums\LiveWagonStatus;
use App\Models\Loader;
use App\Models\Rake;
use App\Models\RakeWagonLoading;
use App\Models\Siding;
use App\Models\User;
use App\Models\Wagon;
use App\Services\LiveMonitor\LiveMonitorDataBuilder;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->builder = app(LiveMonitorDataBuilder::class);

    $this->siding = Siding::factory()->create(['name' => 'Test Siding', 'code' => 'TST']);
    $this->loader = Loader::query()->create([
        'siding_id' => $this->siding->id,
        'loader_name' => 'Loader-1',
        'code' => 'L1',
        'loader_type' => 'wheel-loader',
        'is_active' => true,
    ]);

    $this->rake = Rake::factory()->create([
        'siding_id' => $this->siding->id,
        'rake_number' => 'TEST-001',
        'wagon_count' => 3,
        'placement_time' => now()->subMinutes(60),
        'loading_start_time' => now()->subMinutes(50),
        'loading_free_minutes' => 180,
    ]);

    $this->wagons = collect(range(1, 3))->map(fn (int $seq): Wagon => Wagon::factory()->create([
        'rake_id' => $this->rake->id,
        'wagon_sequence' => $seq,
        'wagon_number' => 'W'.$seq,
        'pcc_weight_mt' => 65,
        'is_unfit' => false,
    ]));

    // Wagon 1: fully loaded, Wagon 2: partial, Wagon 3: empty.
    RakeWagonLoading::query()->create([
        'rake_id' => $this->rake->id,
        'wagon_id' => $this->wagons[0]->id,
        'loader_id' => $this->loader->id,
        'loaded_quantity_mt' => 65,
        'weight_source' => 'manual',
    ]);
    RakeWagonLoading::query()->create([
        'rake_id' => $this->rake->id,
        'wagon_id' => $this->wagons[1]->id,
        'loader_id' => $this->loader->id,
        'loaded_quantity_mt' => 30,
        'weight_source' => 'manual',
    ]);
});

test('forRake returns full payload shape with all sections', function (): void {
    $payload = $this->builder->forRake($this->rake);

    expect($payload)->toHaveKeys([
        'rake', 'kpis', 'status_counts', 'wagons', 'loaders',
        'time_status', 'loading_progress', 'alerts', 'last_event_at',
    ]);
});

test('forRake derives correct status counts', function (): void {
    $payload = $this->builder->forRake($this->rake);

    expect($payload['status_counts'][LiveWagonStatus::Loaded->value])->toBe(1);
    expect($payload['status_counts'][LiveWagonStatus::Loading->value])->toBe(1);
    expect($payload['status_counts'][LiveWagonStatus::Empty->value])->toBe(1);
});

test('forRake computes total loaded MT correctly', function (): void {
    $payload = $this->builder->forRake($this->rake);

    expect($payload['kpis']['total_loaded_mt'])->toBe(95.0);
});

test('forRake computes loading progress percent correctly', function (): void {
    $payload = $this->builder->forRake($this->rake);

    expect($payload['loading_progress']['loaded'])->toBe(1);
    expect($payload['loading_progress']['total'])->toBe(3);
    expect($payload['loading_progress']['percent'])->toBe(round(100 / 3, 1));
});

test('forRake exposes time status anchored at placement_time', function (): void {
    $payload = $this->builder->forRake($this->rake);

    expect($payload['time_status']['anchor'])->toBe('placement');
    expect($payload['time_status']['allowed_minutes'])->toBe(180);
    expect($payload['time_status']['elapsed_minutes'])->toBeGreaterThanOrEqual(59);
    expect($payload['time_status']['elapsed_minutes'])->toBeLessThanOrEqual(61);
});

test('forRake gracefully handles missing placement_time by falling back to loading_start_time', function (): void {
    $this->rake->update(['placement_time' => null]);

    $payload = $this->builder->forRake($this->rake->fresh());

    expect($payload['time_status']['anchor'])->toBe('loading_start');
    expect($payload['time_status']['anchor_at'])->not->toBeNull();
});

test('forRake returns null elapsed_minutes when no anchor available', function (): void {
    $this->rake->update([
        'placement_time' => null,
        'loading_start_time' => null,
    ]);
    RakeWagonLoading::query()->where('rake_id', $this->rake->id)->delete();

    $payload = $this->builder->forRake($this->rake->fresh());

    expect($payload['time_status']['anchor_at'])->toBeNull();
    expect($payload['time_status']['elapsed_minutes'])->toBeNull();
    expect($payload['time_status']['remaining_minutes'])->toBeNull();
});

test('forRake exposes loader activity for active loaders', function (): void {
    $payload = $this->builder->forRake($this->rake);

    expect($payload['loaders'])->toHaveCount(1);
    expect($payload['loaders'][0]['loader_id'])->toBe($this->loader->id);
    expect($payload['loaders'][0]['wagons_completed'])->toBe(2);
});

test('forOverview returns sidings the user can access', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $user->sidings()->attach($this->siding->id, ['is_primary' => true]);

    $payload = $this->builder->forOverview($user);

    expect($payload['sidings'])->toHaveCount(1);
    expect($payload['sidings'][0]['siding_id'])->toBe($this->siding->id);
    expect($payload['sidings'][0]['has_active_rake'])->toBeTrue();
});

test('forOverview excludes sidings the user cannot see', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    // No sidings attached.

    $payload = $this->builder->forOverview($user);

    expect($payload['sidings'])->toBeEmpty();
    expect($payload['totals']['sidings'])->toBe(0);
});

test('forOverview totals sum across visible sidings', function (): void {
    $user = User::factory()->withoutTwoFactor()->create();
    $user->sidings()->attach($this->siding->id, ['is_primary' => true]);

    $payload = $this->builder->forOverview($user);

    expect($payload['totals']['sidings'])->toBe(1);
    expect($payload['totals']['rakes_active'])->toBe(1);
});

test('overload wagons contribute to overload_mt sum', function (): void {
    $w4 = Wagon::factory()->create([
        'rake_id' => $this->rake->id,
        'wagon_sequence' => 4,
        'pcc_weight_mt' => 65,
        'is_unfit' => false,
    ]);
    RakeWagonLoading::query()->create([
        'rake_id' => $this->rake->id,
        'wagon_id' => $w4->id,
        'loaded_quantity_mt' => 70,
        'weight_source' => 'manual',
    ]);

    $payload = $this->builder->forRake($this->rake);

    expect($payload['kpis']['total_overload_mt'])->toBe(5.0);
    expect($payload['status_counts'][LiveWagonStatus::Overload->value])->toBe(1);
});

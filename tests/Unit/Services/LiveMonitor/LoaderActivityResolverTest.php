<?php

declare(strict_types=1);

use App\Models\Loader;
use App\Models\Rake;
use App\Models\RakeWagonLoading;
use App\Models\Siding;
use App\Models\Wagon;
use App\Services\LiveMonitor\LoaderActivityResolver;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->siding = Siding::factory()->create();
    $this->rake = Rake::factory()->create([
        'siding_id' => $this->siding->id,
        'wagon_count' => 3,
    ]);

    $this->wagons = collect(range(1, 3))->map(fn (int $seq): Wagon => Wagon::factory()->create([
        'rake_id' => $this->rake->id,
        'wagon_sequence' => $seq,
        'wagon_number' => 'W'.mb_str_pad((string) $seq, 2, '0', STR_PAD_LEFT),
        'pcc_weight_mt' => 65,
    ]));
});

function makeLoader(int $sidingId, string $name, ?string $code = null): Loader
{
    static $i = 0;
    $i++;

    return Loader::query()->create([
        'siding_id' => $sidingId,
        'loader_name' => $name,
        'code' => $code ?? 'LDR-'.$i,
        'loader_type' => 'wheel-loader',
        'is_active' => true,
    ]);
}

test('loader with recent wagon_loading update is active and points to latest wagon', function (): void {
    $loader = makeLoader($this->siding->id, 'Loader-A', 'LA');

    RakeWagonLoading::query()->create([
        'rake_id' => $this->rake->id,
        'wagon_id' => $this->wagons[0]->id,
        'loader_id' => $loader->id,
        'loaded_quantity_mt' => 60,
        'weight_source' => 'manual',
        'updated_at' => now()->subMinutes(2),
    ]);
    RakeWagonLoading::query()->create([
        'rake_id' => $this->rake->id,
        'wagon_id' => $this->wagons[1]->id,
        'loader_id' => $loader->id,
        'loaded_quantity_mt' => 30,
        'weight_source' => 'manual',
        'updated_at' => now()->subMinutes(1),
    ]);

    $loadings = $this->rake->wagonLoadings()->with('wagon:id,wagon_number,pcc_weight_mt,is_unfit')->get();
    $loaders = collect([$loader]);

    $rows = (new LoaderActivityResolver)->resolveForRake($loaders, $loadings);

    expect($rows)->toHaveCount(1);
    expect($rows[0]['status'])->toBe('active');
    expect($rows[0]['current_wagon_id'])->toBe($this->wagons[1]->id);
    expect($rows[0]['wagons_completed'])->toBe(2);
});

test('loader with stale activity is idle', function (): void {
    $loader = makeLoader($this->siding->id, 'Loader-B');

    RakeWagonLoading::query()->create([
        'rake_id' => $this->rake->id,
        'wagon_id' => $this->wagons[0]->id,
        'loader_id' => $loader->id,
        'loaded_quantity_mt' => 60,
        'weight_source' => 'manual',
        'updated_at' => now()->subMinutes(LoaderActivityResolver::ACTIVITY_WINDOW_MINUTES + 5),
    ]);

    $loadings = $this->rake->wagonLoadings()->with('wagon:id,wagon_number,pcc_weight_mt,is_unfit')->get();
    $rows = (new LoaderActivityResolver)->resolveForRake(collect([$loader]), $loadings);

    expect($rows[0]['status'])->toBe('idle');
    expect($rows[0]['current_wagon_id'])->toBeNull();
    expect($rows[0]['wagons_completed'])->toBe(1);
});

test('loader with no rows shows up as idle with zero completed', function (): void {
    $loader = makeLoader($this->siding->id, 'Loader-C');

    $rows = (new LoaderActivityResolver)
        ->resolveForRake(collect([$loader]), collect());

    expect($rows[0]['status'])->toBe('idle');
    expect($rows[0]['wagons_completed'])->toBe(0);
    expect($rows[0]['last_active_at'])->toBeNull();
});

test('active loaders are sorted before idle ones', function (): void {
    $idle = makeLoader($this->siding->id, 'AAA-Idle');
    $active = makeLoader($this->siding->id, 'ZZZ-Active');

    RakeWagonLoading::query()->create([
        'rake_id' => $this->rake->id,
        'wagon_id' => $this->wagons[0]->id,
        'loader_id' => $active->id,
        'loaded_quantity_mt' => 60,
        'weight_source' => 'manual',
        'updated_at' => now()->subMinute(),
    ]);

    $loadings = $this->rake->wagonLoadings()->with('wagon:id,wagon_number,pcc_weight_mt,is_unfit')->get();
    $rows = (new LoaderActivityResolver)->resolveForRake(collect([$idle, $active]), $loadings);

    expect($rows[0]['loader_id'])->toBe($active->id);
    expect($rows[1]['loader_id'])->toBe($idle->id);
});

<?php

declare(strict_types=1);

use App\Models\Rake;
use App\Models\Siding;
use App\Models\Wagon;
use App\Models\WagonLoading;
use App\Services\ManagerBrief\LiveExposureCalculator;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->calculator = app(LiveExposureCalculator::class);
});

it('sums overload rs across active rakes', function (): void {
    $siding = Siding::factory()->create();

    $rake = Rake::factory()->create([
        'siding_id' => $siding->id,
        'state' => 'loading',
    ]);

    // Three wagons each with pcc_weight_mt = 68
    $wagon1 = Wagon::factory()->create([
        'rake_id' => $rake->id,
        'pcc_weight_mt' => 68.0,
        'is_unfit' => false,
    ]);
    $wagon2 = Wagon::factory()->create([
        'rake_id' => $rake->id,
        'pcc_weight_mt' => 68.0,
        'is_unfit' => false,
    ]);
    $wagon3 = Wagon::factory()->create([
        'rake_id' => $rake->id,
        'pcc_weight_mt' => 68.0,
        'is_unfit' => false,
    ]);

    // Two within PCC (66.0 MT each) — no overload
    WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon1->id,
        'loaded_quantity_mt' => 66.0,
        'weight_source' => 'manual',
    ]);
    WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon2->id,
        'loaded_quantity_mt' => 66.0,
        'weight_source' => 'manual',
    ]);

    // One over PCC (70.0 MT — overload 2.0 MT)
    WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon3->id,
        'loaded_quantity_mt' => 70.0,
        'weight_source' => 'manual',
    ]);

    $result = $this->calculator->handle((int) $siding->id);

    expect($result['total_rs'])->toBeGreaterThan(0.0);
    expect($result['breakdown'])->toHaveCount(1);
    expect($result['breakdown'][0]['rake_id'])->toBe((int) $rake->id);
    expect($result['breakdown'][0]['overload_mt'])->toBe(2.0);
    expect($result['breakdown'][0]['rs'])->toBeGreaterThan(0.0);
});

it('ignores ghost rakes with zero loaded and no loadrite events', function (): void {
    $siding = Siding::factory()->create();

    // First rake with actual loading activity
    $rake1 = Rake::factory()->create([
        'siding_id' => $siding->id,
        'state' => 'loading',
    ]);
    $wagon1 = Wagon::factory()->create([
        'rake_id' => $rake1->id,
        'pcc_weight_mt' => 68.0,
        'is_unfit' => false,
    ]);
    WagonLoading::factory()->create([
        'rake_id' => $rake1->id,
        'wagon_id' => $wagon1->id,
        'loaded_quantity_mt' => 70.0,
        'weight_source' => 'manual',
    ]);

    // Ghost rake: no wagon_loading rows AND no loadrite_events
    $rake2 = Rake::factory()->create([
        'siding_id' => $siding->id,
        'state' => 'placed',
    ]);
    // Create a wagon for ghost rake but no loading row
    Wagon::factory()->create([
        'rake_id' => $rake2->id,
        'pcc_weight_mt' => 68.0,
        'is_unfit' => false,
    ]);

    $result = $this->calculator->handle((int) $siding->id);

    $rakeIds = array_column($result['breakdown'], 'rake_id');
    expect($rakeIds)->not->toContain((int) $rake2->id);
});

it('excludes weighbridge weight source rows', function (): void {
    $siding = Siding::factory()->create();

    $rake = Rake::factory()->create([
        'siding_id' => $siding->id,
        'state' => 'loading',
    ]);
    $wagon = Wagon::factory()->create([
        'rake_id' => $rake->id,
        'pcc_weight_mt' => 68.0,
        'is_unfit' => false,
    ]);

    // Weighbridge source with positive loaded_quantity_mt over PCC
    WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'loaded_quantity_mt' => 75.0,
        'weight_source' => 'weighbridge',
    ]);

    $result = $this->calculator->handle((int) $siding->id);

    expect($result['total_rs'])->toBe(0.0);
    expect($result['breakdown'])->toBeEmpty();
});

it('returns zero when no active rakes', function (): void {
    $siding = Siding::factory()->create();

    // No rakes in loading/placed state
    Rake::factory()->create([
        'siding_id' => $siding->id,
        'state' => 'dispatched',
    ]);

    $result = $this->calculator->handle((int) $siding->id);

    expect($result['total_rs'])->toBe(0.0);
    expect($result['breakdown'])->toBeEmpty();
});

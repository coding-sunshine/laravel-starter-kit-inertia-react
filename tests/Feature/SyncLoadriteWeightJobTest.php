<?php

declare(strict_types=1);

use App\Jobs\SyncLoadriteWeightJob;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\Wagon;
use App\Models\WagonLoading;

it('updates loadrite_weight_mt and sets weight_source to loadrite for manual records', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_number' => 5, 'rake_id' => $rake->id]);
    $loading = WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'weight_source' => 'manual',
        'loadrite_override' => false,
    ]);

    (new SyncLoadriteWeightJob(['Sequence' => 5, 'Weight' => 62.4, 'Timestamp' => now()->toIso8601String()], $siding->id))->handle();

    $loading->refresh();
    expect((float) $loading->loadrite_weight_mt)->toBe(62.4);
    expect($loading->weight_source)->toBe('loadrite');
});

it('does not overwrite weighbridge records', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_number' => 3, 'rake_id' => $rake->id]);
    $loading = WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'loaded_quantity_mt' => 67.0,
        'weight_source' => 'weighbridge',
    ]);

    (new SyncLoadriteWeightJob(['Sequence' => 3, 'Weight' => 50.0, 'Timestamp' => now()->toIso8601String()], $siding->id))->handle();

    $loading->refresh();
    expect($loading->weight_source)->toBe('weighbridge');
    expect($loading->loadrite_weight_mt)->toBeNull();
});

it('logs warning and skips when no matching wagon loading found', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'loading']);

    Illuminate\Support\Facades\Log::shouldReceive('warning')->once();

    (new SyncLoadriteWeightJob(['Sequence' => 99, 'Weight' => 50.0, 'Timestamp' => now()->toIso8601String()], $siding->id))->handle();
});

it('does not update weight_source when loadrite_override is true', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_number' => 7, 'rake_id' => $rake->id]);
    $loading = WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'weight_source' => 'manual',
        'loadrite_override' => true,
    ]);

    (new SyncLoadriteWeightJob(['Sequence' => 7, 'Weight' => 55.0, 'Timestamp' => now()->toIso8601String()], $siding->id))->handle();

    $loading->refresh();
    expect((float) $loading->loadrite_weight_mt)->toBe(55.0);
    expect($loading->weight_source)->toBe('manual'); // unchanged because override=true
});

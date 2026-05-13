<?php

declare(strict_types=1);

use App\Actions\SyncLoadriteEvent;
use App\Models\LoadriteEvent;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\Wagon;
use App\Models\WagonLoading;

function makeEvent(string $id, int $sequence, float $weight, string $type = 'Add', ?string $time = null): array
{
    return [
        'Id' => $id,
        'Sequence' => (string) $sequence,
        'Weight' => (string) $weight,
        'Event' => $type,
        'Time' => $time ?? now()->format('Y-m-d H:i:s'),
        'Operator' => 'TestOp',
        'Scale ID' => '11',
        'Product' => 'COAL',
    ];
}

it('accumulates Add events into cumulative loaded_quantity_mt', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_sequence' => 5, 'rake_id' => $rake->id]);
    $loading = WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'weight_source' => 'manual',
        'loadrite_override' => false,
    ]);

    $action = app(SyncLoadriteEvent::class);

    expect($action->handle(makeEvent('evt-1', 5, 6.2), $siding->id))->toBeTrue();
    expect($action->handle(makeEvent('evt-2', 5, 6.5), $siding->id))->toBeTrue();
    expect($action->handle(makeEvent('evt-3', 5, 6.8), $siding->id))->toBeTrue();

    $loading->refresh();
    expect((float) $loading->loaded_quantity_mt)->toEqualWithDelta(19.5, 0.001);
    expect((float) $loading->loadrite_weight_mt)->toEqualWithDelta(19.5, 0.001);
    expect($loading->weight_source)->toBe('loadrite');
});

it('subtracts Subtract events from the running total', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_sequence' => 7, 'rake_id' => $rake->id]);
    $loading = WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'weight_source' => 'manual',
        'loadrite_override' => false,
    ]);

    $action = app(SyncLoadriteEvent::class);
    $action->handle(makeEvent('add-1', 7, 10.0, 'Add'), $siding->id);
    $action->handle(makeEvent('add-2', 7, 5.0, 'Add'), $siding->id);
    $action->handle(makeEvent('sub-1', 7, 3.0, 'Subtract'), $siding->id);

    $loading->refresh();
    expect((float) $loading->loaded_quantity_mt)->toEqualWithDelta(12.0, 0.001);
});

it('is idempotent on event_id — replaying the same event does not double-count', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_sequence' => 9, 'rake_id' => $rake->id]);
    $loading = WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'weight_source' => 'manual',
        'loadrite_override' => false,
    ]);

    $action = app(SyncLoadriteEvent::class);
    expect($action->handle(makeEvent('dup-id', 9, 8.0), $siding->id))->toBeTrue();
    expect($action->handle(makeEvent('dup-id', 9, 8.0), $siding->id))->toBeFalse();
    expect($action->handle(makeEvent('dup-id', 9, 8.0), $siding->id))->toBeFalse();

    expect(LoadriteEvent::where('event_id', 'dup-id')->count())->toBe(1);
    $loading->refresh();
    expect((float) $loading->loaded_quantity_mt)->toEqualWithDelta(8.0, 0.001);
});

it('does not overwrite weighbridge records', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_sequence' => 3, 'rake_id' => $rake->id]);
    $loading = WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'loaded_quantity_mt' => 67.0,
        'weight_source' => 'weighbridge',
    ]);

    app(SyncLoadriteEvent::class)->handle(makeEvent('wb-test', 3, 50.0), $siding->id);

    $loading->refresh();
    expect($loading->weight_source)->toBe('weighbridge');
    expect((float) $loading->loaded_quantity_mt)->toBe(67.0);
    // Event still persisted for audit even though wagon_loading untouched.
    expect(LoadriteEvent::where('event_id', 'wb-test')->exists())->toBeTrue();
});

it('respects loadrite_override — updates loadrite_weight_mt but not loaded_quantity_mt', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'loading']);
    $wagon = Wagon::factory()->create(['wagon_sequence' => 11, 'rake_id' => $rake->id]);
    $loading = WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'loaded_quantity_mt' => 42.0,
        'weight_source' => 'manual',
        'loadrite_override' => true,
    ]);

    app(SyncLoadriteEvent::class)->handle(makeEvent('ov-1', 11, 55.0), $siding->id);

    $loading->refresh();
    expect((float) $loading->loadrite_weight_mt)->toEqualWithDelta(55.0, 0.001);
    expect((float) $loading->loaded_quantity_mt)->toBe(42.0); // manual entry preserved
    expect($loading->weight_source)->toBe('manual');
});

it('skips events with Sequence 0 (tare events)', function (): void {
    $siding = Siding::factory()->create();

    expect(app(SyncLoadriteEvent::class)->handle(makeEvent('tare-1', 0, 0.5), $siding->id))->toBeFalse();
    expect(LoadriteEvent::where('event_id', 'tare-1')->exists())->toBeFalse();
});

it('persists orphan events (no matching wagon) for audit with nullable FKs', function (): void {
    $siding = Siding::factory()->create();

    app(SyncLoadriteEvent::class)->handle(makeEvent('orphan-1', 99, 5.5), $siding->id);

    $persisted = LoadriteEvent::where('event_id', 'orphan-1')->first();
    expect($persisted)->not->toBeNull();
    expect($persisted->rake_id)->toBeNull();
    expect($persisted->wagon_id)->toBeNull();
    expect($persisted->wagon_sequence)->toBe(99);
});

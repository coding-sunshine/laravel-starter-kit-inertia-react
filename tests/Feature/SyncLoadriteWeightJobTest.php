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

// Note: as of commit 8434daad ("wagon weights from Short Total events, not
// sequence sum"), Sequence is a per-wagon bucket slot, not a stable wagon
// identifier, so Add/Subtract events no longer drive cumulative weight — only
// "Short Total" events (one per completed wagon) do. These tests were
// rewritten to match that model; the old Add/Subtract accumulation scenarios
// they replace no longer reflect intended behavior.
it('attributes a Short Total event weight to the next unfilled wagon', function (): void {
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

    expect($action->handle(makeEvent('evt-1', 5, 65.0, 'Short Total'), $siding->id))->toBeTrue();

    $loading->refresh();
    expect((float) $loading->loaded_quantity_mt)->toEqualWithDelta(65.0, 0.001);
    expect((float) $loading->loadrite_weight_mt)->toEqualWithDelta(65.0, 0.001);
    expect($loading->weight_source)->toBe('loadrite');
});

it('persists Add/Subtract events for audit without affecting loaded_quantity_mt', function (): void {
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
    expect($action->handle(makeEvent('add-1', 7, 10.0, 'Add'), $siding->id))->toBeTrue();
    expect($action->handle(makeEvent('add-2', 7, 5.0, 'Add'), $siding->id))->toBeTrue();
    expect($action->handle(makeEvent('sub-1', 7, 3.0, 'Subtract'), $siding->id))->toBeTrue();

    expect(LoadriteEvent::whereIn('event_id', ['add-1', 'add-2', 'sub-1'])->count())->toBe(3);
    $loading->refresh();
    expect($loading->loaded_quantity_mt)->toBeNull();
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
    expect($action->handle(makeEvent('dup-id', 9, 40.0, 'Short Total'), $siding->id))->toBeTrue();
    expect($action->handle(makeEvent('dup-id', 9, 40.0, 'Short Total'), $siding->id))->toBeFalse();
    expect($action->handle(makeEvent('dup-id', 9, 40.0, 'Short Total'), $siding->id))->toBeFalse();

    expect(LoadriteEvent::where('event_id', 'dup-id')->count())->toBe(1);
    $loading->refresh();
    expect((float) $loading->loaded_quantity_mt)->toEqualWithDelta(40.0, 0.001);
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

    app(SyncLoadriteEvent::class)->handle(makeEvent('wb-test', 3, 50.0, 'Short Total'), $siding->id);

    $loading->refresh();
    expect($loading->weight_source)->toBe('weighbridge');
    expect((float) $loading->loaded_quantity_mt)->toBe(67.0);
    // Event still persisted for audit even though wagon_loading untouched.
    expect(LoadriteEvent::where('event_id', 'wb-test')->exists())->toBeTrue();
});

it('skips a loadrite_override wagon and attributes the next unfilled wagon instead', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'loading']);
    $overriddenWagon = Wagon::factory()->create(['wagon_sequence' => 11, 'rake_id' => $rake->id]);
    $nextWagon = Wagon::factory()->create(['wagon_sequence' => 12, 'rake_id' => $rake->id]);
    $overriddenLoading = WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $overriddenWagon->id,
        'loaded_quantity_mt' => 42.0,
        'weight_source' => 'manual',
        'loadrite_override' => true,
    ]);
    $nextLoading = WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $nextWagon->id,
        'weight_source' => 'manual',
        'loadrite_override' => false,
    ]);

    app(SyncLoadriteEvent::class)->handle(makeEvent('ov-1', 11, 55.0, 'Short Total'), $siding->id);

    $overriddenLoading->refresh();
    $nextLoading->refresh();
    expect($overriddenLoading->loadrite_weight_mt)->toBeNull(); // override wagon untouched
    expect((float) $overriddenLoading->loaded_quantity_mt)->toBe(42.0); // manual entry preserved
    expect($overriddenLoading->weight_source)->toBe('manual');
    expect((float) $nextLoading->loaded_quantity_mt)->toEqualWithDelta(55.0, 0.001); // routed to next unfilled wagon
});

it('skips low-weight Short Total events as an operator misfire', function (): void {
    $siding = Siding::factory()->create();

    expect(app(SyncLoadriteEvent::class)->handle(makeEvent('misfire-1', 1, 0.5, 'Short Total'), $siding->id))->toBeTrue();
    // Event still persisted for audit even though it fell below MIN_VALID_SHORT_TOTAL_MT.
    expect(LoadriteEvent::where('event_id', 'misfire-1')->exists())->toBeTrue();
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

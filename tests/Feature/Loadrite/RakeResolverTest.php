<?php

declare(strict_types=1);

use App\Actions\SyncLoadriteEvent;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\Wagon;
use App\Models\WagonLoading;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('prefers the rake with recent wagon_loading activity (active window)', function (): void {
    $siding = Siding::factory()->create();
    $oldRake = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'pending', 'loading_date' => now()->subDays(5)]);
    $activeRake = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'pending', 'loading_date' => now()->subDays(5)]);

    $oldWagon = Wagon::factory()->create(['rake_id' => $oldRake->id]);
    $activeWagon = Wagon::factory()->create(['rake_id' => $activeRake->id]);

    WagonLoading::factory()->create([
        'rake_id' => $oldRake->id,
        'wagon_id' => $oldWagon->id,
        'updated_at' => now()->subHours(48), // way past the 6h window
    ]);
    WagonLoading::factory()->create([
        'rake_id' => $activeRake->id,
        'wagon_id' => $activeWagon->id,
        'updated_at' => now()->subHour(),    // inside the 6h window
    ]);

    $picked = app(SyncLoadriteEvent::class)->resolveRakeIdForEvent($siding->id, Carbon::now());

    expect($picked)->toBe($activeRake->id);
});

it('falls back to the newest pending rake at the siding when no rake is active', function (): void {
    $siding = Siding::factory()->create();
    Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'pending', 'loading_date' => now()->subDays(2)]);
    $newest = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'pending', 'loading_date' => now()->toDateString()]);

    // No wagon_loading rows anywhere — nothing is "active".

    $picked = app(SyncLoadriteEvent::class)->resolveRakeIdForEvent($siding->id, Carbon::now());

    expect($picked)->toBe($newest->id);
});

it('treats a rake as stale once its last wagon_loading activity is past the active window', function (): void {
    $siding = Siding::factory()->create();
    $stale = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'pending', 'loading_date' => now()->subDays(2)]);
    $fresh = Rake::factory()->create(['siding_id' => $siding->id, 'state' => 'pending', 'loading_date' => now()->toDateString()]);

    $wagon = Wagon::factory()->create(['rake_id' => $stale->id]);
    WagonLoading::factory()->create([
        'rake_id' => $stale->id,
        'wagon_id' => $wagon->id,
        'updated_at' => now()->subHours(12), // outside 6h window
    ]);

    // The stale rake's only wagon_loading is too old; resolver must pick the
    // newer pending rake even though the stale one has wagon_loadings.
    $picked = app(SyncLoadriteEvent::class)->resolveRakeIdForEvent($siding->id, Carbon::now());

    expect($picked)->toBe($fresh->id);
});

it('still attributes to an old pending rake if it is the only candidate', function (): void {
    // We deliberately do NOT drop events on the floor. The least-bad attribution
    // is the newest pending rake at the siding, even if loading_date is old.
    $siding = Siding::factory()->create();
    $only = Rake::factory()->create([
        'siding_id' => $siding->id,
        'state' => 'pending',
        'loading_date' => now()->subDays(30),
    ]);

    $picked = app(SyncLoadriteEvent::class)->resolveRakeIdForEvent($siding->id, Carbon::now());

    expect($picked)->toBe($only->id);
});

it('matches the operator-keyed rake number to rake_serial_number', function (): void {
    $siding = Siding::factory()->create();
    // A newer pending rake exists, but the operator keyed rake "80" — the
    // resolver must honour the explicit serial number over recency.
    Rake::factory()->create([
        'siding_id' => $siding->id,
        'state' => 'pending',
        'rake_serial_number' => '79',
        'loading_date' => now()->toDateString(),
    ]);
    $target = Rake::factory()->create([
        'siding_id' => $siding->id,
        'state' => 'pending',
        'rake_serial_number' => '80',
        'loading_date' => now()->subDays(2),
    ]);
    Rake::factory()->create([
        'siding_id' => $siding->id,
        'state' => 'pending',
        'rake_serial_number' => '81',
        'loading_date' => now()->toDateString(),
    ]);

    $picked = app(SyncLoadriteEvent::class)
        ->resolveRakeIdForEvent($siding->id, Carbon::now(), '80');

    expect($picked)->toBe($target->id);
});

it('falls back to time-based resolution when the keyed rake number has no match', function (): void {
    $siding = Siding::factory()->create();
    $only = Rake::factory()->create([
        'siding_id' => $siding->id,
        'state' => 'pending',
        'rake_serial_number' => '12',
        'loading_date' => now()->toDateString(),
    ]);

    // Keyed rake "999" matches nothing → resolver falls through to the
    // newest pending rake.
    $picked = app(SyncLoadriteEvent::class)
        ->resolveRakeIdForEvent($siding->id, Carbon::now(), '999');

    expect($picked)->toBe($only->id);
});

it('honors loading_end_time and ignores closed rakes', function (): void {
    $siding = Siding::factory()->create();
    $closed = Rake::factory()->create([
        'siding_id' => $siding->id,
        'state' => 'pending',
        'loading_date' => now()->toDateString(),
        'loading_end_time' => now()->subMinutes(10), // closed before event_time
    ]);
    $open = Rake::factory()->create([
        'siding_id' => $siding->id,
        'state' => 'pending',
        'loading_date' => now()->toDateString(),
    ]);

    $picked = app(SyncLoadriteEvent::class)->resolveRakeIdForEvent($siding->id, Carbon::now());

    expect($picked)->toBe($open->id);
});

it('attribution auto-creates wagon_loading rows for a fresh rake', function (): void {
    // Postgres-specific casts inside SyncLoadriteEvent::attributeShortTotal
    // can't run against SQLite's in-memory test DB. Behaviour is verified by
    // the live post-deploy DB check.
    if (DB::connection()->getDriverName() === 'sqlite') {
        test()->markTestSkipped('attribution path uses Postgres casts');
    }
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create([
        'siding_id' => $siding->id,
        'state' => 'pending',
        'loading_date' => now()->toDateString(),
        'wagon_count' => 3,
    ]);
    // 3 wagons exist but no wagon_loading rows yet — exactly the broken-prod
    // state.
    Wagon::factory()->create(['rake_id' => $rake->id, 'wagon_sequence' => 1, 'pcc_weight_mt' => 70]);
    Wagon::factory()->create(['rake_id' => $rake->id, 'wagon_sequence' => 2, 'pcc_weight_mt' => 70]);
    Wagon::factory()->create(['rake_id' => $rake->id, 'wagon_sequence' => 3, 'pcc_weight_mt' => 70]);

    expect(DB::table('wagon_loading')->where('rake_id', $rake->id)->count())->toBe(0);

    $event = [
        'Id' => 'st-1',
        'Sequence' => '1',
        'Weight' => '65.5',
        'Event' => 'Short Total',
        'Time' => now()->format('Y-m-d H:i:s'),
        'Operator' => 'Op',
        'Scale ID' => '11',
        'Product' => 'COAL',
    ];

    expect(app(SyncLoadriteEvent::class)->handle($event, $siding->id))->toBeTrue();

    // All 3 wagon_loading rows materialised; the first one has the weight.
    expect(DB::table('wagon_loading')->where('rake_id', $rake->id)->count())->toBe(3);
    $filled = DB::table('wagon_loading')
        ->join('wagons', 'wagons.id', '=', 'wagon_loading.wagon_id')
        ->where('wagon_loading.rake_id', $rake->id)
        ->orderBy('wagons.wagon_sequence')
        ->first();
    expect((float) $filled->loaded_quantity_mt)->toEqualWithDelta(65.5, 0.001);
});

<?php

declare(strict_types=1);

use App\Actions\SyncLoadriteEvent;
use App\Models\LoadriteAnomaly;
use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helper: minimal Add event (non-Short-Total) that routes through
// SyncLoadriteEvent. We use 'Add' so the anomaly-recording path is exercised
// without triggering attributeShortTotal(), which contains a Postgres-only
// `::numeric` cast that fails on SQLite (pre-existing issue, not introduced here).
// ---------------------------------------------------------------------------

function shortTotalEvent(array $overrides = []): array
{
    return array_merge([
        'Id' => uniqid('ev', true),
        'Event' => 'Add',  // Use Add to bypass attributeShortTotal (SQLite compat)
        'Weight' => '66.9',
        'Time' => now()->subHour()->toDateTimeString(),
        'Scale ID' => '16',
        'UserData1' => '80',
        'UserData2' => '12001',
        'UserData3' => 'HL',
    ], $overrides);
}

// ---------------------------------------------------------------------------
// wagon_type_unmappable
// ---------------------------------------------------------------------------

it('records wagon_type_unmappable anomaly when wagon_type cannot be normalised', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    Rake::factory()->create([
        'siding_id' => $sidingId,
        'state' => 'loading',
        'placement_time' => now()->subHours(4),
    ]);

    // UserData3 = 'XXXXXXXX' — not in any catalog → normaliser returns null.
    $event = shortTotalEvent(['UserData3' => 'XXXXXXXX']);

    app(SyncLoadriteEvent::class)->handle($event, $sidingId);

    expect(LoadriteAnomaly::query()
        ->where('siding_id', $sidingId)
        ->where('kind', 'wagon_type_unmappable')
        ->where('raw_value', 'XXXXXXXX')
        ->exists()
    )->toBeTrue();
});

it('does not record wagon_type_unmappable when wagon_type normalises successfully', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    Rake::factory()->create([
        'siding_id' => $sidingId,
        'state' => 'loading',
        'placement_time' => now()->subHours(4),
    ]);

    // UserData3 = 'HL' — in type_abbreviations → normalises to 'HL'.
    $event = shortTotalEvent(['UserData3' => 'HL']);

    app(SyncLoadriteEvent::class)->handle($event, $sidingId);

    expect(LoadriteAnomaly::query()
        ->where('siding_id', $sidingId)
        ->where('kind', 'wagon_type_unmappable')
        ->exists()
    )->toBeFalse();
});

// ---------------------------------------------------------------------------
// bogus_timestamp
// ---------------------------------------------------------------------------

it('records bogus_timestamp anomaly when event_time is in the future', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    Rake::factory()->create([
        'siding_id' => $sidingId,
        'state' => 'loading',
        'placement_time' => now()->subHours(4),
    ]);

    $futureTime = now()->addYears(10)->toDateTimeString();
    $event = shortTotalEvent(['Time' => $futureTime]);

    app(SyncLoadriteEvent::class)->handle($event, $sidingId);

    expect(LoadriteAnomaly::query()
        ->where('siding_id', $sidingId)
        ->where('kind', 'bogus_timestamp')
        ->exists()
    )->toBeTrue();
});

it('does not record bogus_timestamp for a valid past event_time', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    Rake::factory()->create([
        'siding_id' => $sidingId,
        'state' => 'loading',
        'placement_time' => now()->subHours(4),
    ]);

    $event = shortTotalEvent(['Time' => now()->subHour()->toDateTimeString()]);

    app(SyncLoadriteEvent::class)->handle($event, $sidingId);

    expect(LoadriteAnomaly::query()
        ->where('siding_id', $sidingId)
        ->where('kind', 'bogus_timestamp')
        ->exists()
    )->toBeFalse();
});

// ---------------------------------------------------------------------------
// Factory smoke test
// ---------------------------------------------------------------------------

it('LoadriteAnomalyFactory creates rows', function (): void {
    $siding = Siding::factory()->create();

    $anomaly = LoadriteAnomaly::factory()->create(['siding_id' => $siding->id]);

    expect($anomaly->id)->toBeInt()
        ->and($anomaly->status)->toBe('open')
        ->and(in_array($anomaly->kind, [
            'wagon_type_unmappable',
            'operator_unmappable',
            'bogus_timestamp',
            'rake_serial_missing',
        ], true))->toBeTrue();
});

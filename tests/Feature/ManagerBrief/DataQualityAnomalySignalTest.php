<?php

declare(strict_types=1);

use App\Actions\ManagerBrief\CollectSignals;
use App\DataTransferObjects\ManagerBrief\Signal;
use App\Models\LoadriteAnomaly;
use App\Models\Siding;
use App\Services\ForceMajeure\Contracts\DowntimePenaltyMatcherContract;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Build a no-op DowntimePenaltyMatcher stub.
 */
function makeNoOpMatcher(): DowntimePenaltyMatcherContract
{
    return new class implements DowntimePenaltyMatcherContract
    {
        public function candidates(int $sidingId, int $lookbackDays = 30, int $minOverlapMinutes = 30): array
        {
            return [];
        }
    };
}

function makeCollectSignalsForDq(): CollectSignals
{
    app()->bind(DowntimePenaltyMatcherContract::class, fn () => makeNoOpMatcher());

    return app(CollectSignals::class);
}

function filterByType(array $signals, string $type): array
{
    return array_values(array_filter($signals, fn (Signal $s) => $s->type === $type));
}

// ---------------------------------------------------------------------------
// data_quality_anomaly signal
// ---------------------------------------------------------------------------

it('emits data_quality_anomaly signal when open anomalies exceed threshold', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    // Create 6 open anomalies (threshold is > 5)
    LoadriteAnomaly::factory()->count(6)->create([
        'siding_id' => $sidingId,
        'status' => 'open',
    ]);

    $signals = makeCollectSignalsForDq()->handle($sidingId);
    $dqSignals = filterByType($signals, 'data_quality_anomaly');

    expect($dqSignals)->toHaveCount(1);

    $signal = $dqSignals[0];

    expect($signal->severity)->toBeIn(['medium', 'high'])
        ->and($signal->rsAtStake)->toBe(0.0)
        ->and($signal->payload['open_count'])->toBe(6);
});

it('does not emit data_quality_anomaly when below threshold', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    // Create exactly 5 open anomalies (threshold is > 5, so 5 should NOT trigger)
    LoadriteAnomaly::factory()->count(5)->create([
        'siding_id' => $sidingId,
        'status' => 'open',
    ]);

    $signals = makeCollectSignalsForDq()->handle($sidingId);
    $dqSignals = filterByType($signals, 'data_quality_anomaly');

    expect($dqSignals)->toHaveCount(0);
});

it('emits high severity when open anomaly count exceeds 20', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    LoadriteAnomaly::factory()->count(21)->create([
        'siding_id' => $sidingId,
        'status' => 'open',
    ]);

    $signals = makeCollectSignalsForDq()->handle($sidingId);
    $dqSignals = filterByType($signals, 'data_quality_anomaly');

    expect($dqSignals)->toHaveCount(1)
        ->and($dqSignals[0]->severity)->toBe('high');
});

it('does not count resolved or ignored anomalies toward threshold', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    // 3 open + 10 resolved + 5 ignored = only 3 open → below threshold
    LoadriteAnomaly::factory()->count(3)->create(['siding_id' => $sidingId, 'status' => 'open']);
    LoadriteAnomaly::factory()->count(10)->create(['siding_id' => $sidingId, 'status' => 'resolved']);
    LoadriteAnomaly::factory()->count(5)->create(['siding_id' => $sidingId, 'status' => 'ignored']);

    $signals = makeCollectSignalsForDq()->handle($sidingId);
    $dqSignals = filterByType($signals, 'data_quality_anomaly');

    expect($dqSignals)->toHaveCount(0);
});

it('payload includes by_kind breakdown', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    LoadriteAnomaly::factory()->count(4)->create([
        'siding_id' => $sidingId,
        'status' => 'open',
        'kind' => 'wagon_type_unmappable',
    ]);

    LoadriteAnomaly::factory()->count(3)->create([
        'siding_id' => $sidingId,
        'status' => 'open',
        'kind' => 'bogus_timestamp',
    ]);

    $signals = makeCollectSignalsForDq()->handle($sidingId);
    $dqSignals = filterByType($signals, 'data_quality_anomaly');

    expect($dqSignals)->toHaveCount(1);

    $byKind = $dqSignals[0]->payload['by_kind'];

    expect($byKind['wagon_type_unmappable'])->toBe(4)
        ->and($byKind['bogus_timestamp'])->toBe(3);
});

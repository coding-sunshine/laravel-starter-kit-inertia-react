<?php

declare(strict_types=1);

use App\Actions\ManagerBrief\RankSignals;
use App\DataTransferObjects\ManagerBrief\Signal;

/**
 * Helper to build a minimal Signal DTO.
 *
 * @param  array{type?: string, severity?: string, rs_at_stake?: float, recency_minutes?: int, actionability?: float, payload?: array<string, mixed>}  $overrides
 */
function makeSignal(array $overrides = []): Signal
{
    return new Signal(
        type: $overrides['type'] ?? 'overload_exposure',
        severity: $overrides['severity'] ?? 'medium',
        rsAtStake: $overrides['rs_at_stake'] ?? 1.0,
        recencyMinutes: $overrides['recency_minutes'] ?? 60,
        actionability: $overrides['actionability'] ?? 1.0,
        payload: $overrides['payload'] ?? [],
    );
}

it('orders by rs recency actionability product', function (): void {
    // score = rs_at_stake * recencyWeight * actionability
    // Signal A: 100 * 1.0 (≤60 min) * 0.9 = 90   → should be rank 1
    // Signal B: 200 * 0.5 (≤4320 min, 3 days) * 0.4 = 40   → should be rank 3
    // Signal C: 50  * 0.8 (≤1440 min, 24 h) * 1.0 = 40 but recency 500 < 1500 → rank 2
    // Wait: B and C tie on score so tie-break by recency_minutes ASC
    // C: recency 500, B: recency 1500 → C before B
    $signalA = makeSignal(['rs_at_stake' => 100.0, 'recency_minutes' => 30,   'actionability' => 0.9]);
    $signalC = makeSignal(['rs_at_stake' => 50.0,  'recency_minutes' => 500,  'actionability' => 1.0]);
    $signalB = makeSignal(['rs_at_stake' => 200.0, 'recency_minutes' => 1500, 'actionability' => 0.4]);

    $action = new RankSignals;
    $result = $action->handle([$signalB, $signalC, $signalA]);

    expect($result)->toHaveCount(3)
        ->and($result[0])->toBe($signalA)  // score 90
        ->and($result[1])->toBe($signalC)  // score 40, recency 500
        ->and($result[2])->toBe($signalB); // score 40, recency 1500
});

it('returns top 15 only when more signals supplied', function (): void {
    // Create 20 signals with distinct scores (rs_at_stake varies, others fixed)
    // Recency ≤60 → weight 1.0; actionability 1.0 → score = rs_at_stake
    $signals = [];

    for ($i = 1; $i <= 20; $i++) {
        $signals[] = makeSignal(['rs_at_stake' => (float) $i, 'recency_minutes' => 30, 'actionability' => 1.0]);
    }

    $action = new RankSignals;
    $result = $action->handle($signals);

    expect($result)->toHaveCount(15);

    // The lowest-ranked returned signal (rank 15) must have score ≥ any dropped signal.
    $lowestReturned = end($result);
    $lowestReturnedScore = $lowestReturned->rsAtStake; // weight=1.0, actionability=1.0

    // Dropped signals are the ones with rs_at_stake 1..5 (scores 1–5).
    // Lowest returned is rs_at_stake=6, score=6 ≥ 5.
    $droppedMax = 5.0; // signal with rs_at_stake=5 was dropped

    expect($lowestReturnedScore)->toBeGreaterThanOrEqual($droppedMax);
});

it('returns empty when input empty', function (): void {
    $action = new RankSignals;
    $result = $action->handle([]);

    expect($result)->toBeEmpty();
});

it('breaks ties by recency', function (): void {
    // Both signals score identically: rs=100 * weight(30 min)=1.0 * actionability=0.5 = 50
    // Tie-break: lower recency_minutes comes first.
    $signalEarlier = makeSignal(['rs_at_stake' => 100.0, 'recency_minutes' => 30,  'actionability' => 0.5]);
    $signalLater = makeSignal(['rs_at_stake' => 100.0, 'recency_minutes' => 59,  'actionability' => 0.5]);

    $action = new RankSignals;
    $result = $action->handle([$signalLater, $signalEarlier]);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBe($signalEarlier)  // recency 30 → first
        ->and($result[1])->toBe($signalLater);   // recency 59 → second
});

it('caps signals per type so one detector cannot fill the brief', function (): void {
    $drift = array_map(
        fn (int $i): Signal => makeSignal(['type' => 'pcc_drift', 'rs_at_stake' => 1_000_000.0 - $i]),
        range(1, 17),
    );
    $other = makeSignal(['type' => 'demurrage_risk', 'rs_at_stake' => 10.0]);

    $result = (new RankSignals)->handle([...$drift, $other]);

    // Only the cap's worth of drift signals rank ahead of the other type;
    // the rest are backfill after every distinct type has had its turn.
    $beforeOther = array_slice($result, 0, 3);

    expect(array_filter($beforeOther, fn (Signal $s): bool => $s->type === 'pcc_drift'))->toHaveCount(3)
        ->and($result[3])->toBe($other);
});

it('ranks zero-rupee signals on a severity notional instead of dropping them', function (): void {
    $compliance = makeSignal(['type' => 'data_quality_anomaly', 'severity' => 'high', 'rs_at_stake' => 0.0]);
    $tiny = makeSignal(['type' => 'demurrage_risk', 'rs_at_stake' => 1.0]);

    $result = (new RankSignals)->handle([$tiny, $compliance]);

    expect($result[0])->toBe($compliance);
});

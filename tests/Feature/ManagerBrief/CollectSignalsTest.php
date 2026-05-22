<?php

declare(strict_types=1);

use App\Actions\ManagerBrief\CollectSignals;
use App\DataTransferObjects\ManagerBrief\Signal;
use App\Models\LoadingOverride;
use App\Models\LoadriteDowntimeEvent;
use App\Models\LoadriteEvent;
use App\Models\PenaltyReconciliation;
use App\Models\Rake;
use App\Models\Siding;
use App\Models\SidingPerformance;
use App\Models\Wagon;
use App\Models\WagonLoading;
use App\Services\ForceMajeure\Contracts\DowntimePenaltyMatcherContract;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Tests\Helpers\SeedsManagerBriefFixture;

uses(SeedsManagerBriefFixture::class);

// ---------------------------------------------------------------------------
// Helper: resolve a fresh CollectSignals instance with an optional matcher stub.
// ---------------------------------------------------------------------------

/**
 * Build a DowntimePenaltyMatcher stub that returns a fixed candidates list.
 *
 * DowntimePenaltyMatcher is final readonly, so we implement the contract instead.
 *
 * @param  list<array<string,mixed>>  $candidates
 */
function makeMatcher(array $candidates = []): DowntimePenaltyMatcherContract
{
    return new class($candidates) implements DowntimePenaltyMatcherContract
    {
        /** @param list<array<string,mixed>> $fixed */
        public function __construct(private readonly array $fixed) {}

        public function candidates(int $sidingId, int $lookbackDays = 30, int $minOverlapMinutes = 30): array
        {
            return $this->fixed;
        }
    };
}

/**
 * Resolve CollectSignals with an optional matcher stub bound in the container.
 */
function makeCollectSignals(?DowntimePenaltyMatcherContract $matcherStub = null): CollectSignals
{
    if ($matcherStub !== null) {
        app()->bind(DowntimePenaltyMatcherContract::class, fn () => $matcherStub);
    }

    return app(CollectSignals::class);
}

// ---------------------------------------------------------------------------
// 7.2 — overload_exposure
// ---------------------------------------------------------------------------

it('emits live overload exposure signal', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    $rake = Rake::factory()->create([
        'siding_id' => $sidingId,
        'state' => 'loading',
        'placement_time' => now()->subHours(4),
    ]);

    $wagon = Wagon::factory()->create([
        'rake_id' => $rake->id,
        'pcc_weight_mt' => 68.0,
        'is_unfit' => false,
    ]);

    // 71 MT loaded → 3 MT overload → severity: high
    WagonLoading::factory()->create([
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'loaded_quantity_mt' => 71.0,
        'weight_source' => 'manual',
    ]);

    // Loadrite event so ghost-rake guard is satisfied.
    LoadriteEvent::create([
        'event_id' => Str::uuid()->toString(),
        'siding_id' => $sidingId,
        'rake_id' => $rake->id,
        'wagon_id' => $wagon->id,
        'wagon_sequence' => 1,
        'event_type' => 'Add',
        'weight_mt' => 71.0,
        'event_time' => now()->subMinutes(10),
        'scale_id' => 'SCALE-1',
    ]);

    $action = makeCollectSignals(makeMatcher());
    $signals = $action->handle($sidingId);

    $overloadSignals = array_filter($signals, fn (Signal $s) => $s->type === 'overload_exposure');
    $overloadSignals = array_values($overloadSignals);

    expect($overloadSignals)->toHaveCount(1);

    $signal = $overloadSignals[0];

    expect($signal->type)->toBe('overload_exposure');
    expect($signal->severity)->toBe('high'); // 3 MT → ≥2 MT, < 5 MT
    expect($signal->rsAtStake)->toBe(3000.0); // 3 MT × 1000 Rs/MT default
    expect($signal->actionability)->toBe(0.9);
    expect($signal->payload)->toHaveKey('rake_id', (int) $rake->id);
    expect($signal->payload)->toHaveKey('rake_number', (string) $rake->rake_number);
    expect($signal->payload['overload_mt'])->toBeFloat();
    expect((float) $signal->payload['overload_mt'])->toBeGreaterThan(0.0);
});

// ---------------------------------------------------------------------------
// 7.3 — operator_anomaly
// ---------------------------------------------------------------------------

it('emits operator anomaly signal when overload rate doubles baseline', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    $today = CarbonImmutable::today();
    $weekStart = $today->startOfWeek()->toDateString();

    // This week: 10 wagons, 5 overloaded → 50% rate.
    $thisWeekRake = Rake::factory()->create([
        'siding_id' => $sidingId,
        'state' => 'loading',
        'loading_date' => $weekStart,
    ]);

    for ($i = 1; $i <= 10; $i++) {
        $w = Wagon::factory()->create(['rake_id' => $thisWeekRake->id, 'pcc_weight_mt' => 68.0, 'is_unfit' => false]);
        WagonLoading::factory()->create([
            'rake_id' => $thisWeekRake->id,
            'wagon_id' => $w->id,
            'loaded_quantity_mt' => $i <= 5 ? 72.0 : 66.0, // 5 overloaded → 50% rate
            'loader_operator_name' => 'Test Operator',
            'cc_capacity_mt' => null,
        ]);
    }

    // Baseline (prior weeks only, 30 days ago): 100 wagons, 1 overloaded → ~1% rate.
    // With this-week included in baseline, total = 110, overloaded = 6 → 5.45%.
    // This-week rate 50% / 5.45% ≈ 9× → severity: high (≥3×).
    $baselineRake = Rake::factory()->create([
        'siding_id' => $sidingId,
        'state' => 'loading',
        'loading_date' => $today->subDays(15)->toDateString(),
    ]);

    for ($i = 1; $i <= 100; $i++) {
        $w = Wagon::factory()->create(['rake_id' => $baselineRake->id, 'pcc_weight_mt' => 68.0, 'is_unfit' => false]);
        WagonLoading::factory()->create([
            'rake_id' => $baselineRake->id,
            'wagon_id' => $w->id,
            'loaded_quantity_mt' => $i === 1 ? 72.0 : 66.0, // 1 overloaded → 1% base
            'loader_operator_name' => 'Test Operator',
            'cc_capacity_mt' => null,
        ]);
    }

    $action = makeCollectSignals(makeMatcher());
    $signals = $action->handle($sidingId);

    $anomalySignals = array_filter($signals, fn (Signal $s) => $s->type === 'operator_anomaly');
    $anomalySignals = array_values($anomalySignals);

    expect($anomalySignals)->toHaveCount(1);

    $signal = $anomalySignals[0];

    expect($signal->type)->toBe('operator_anomaly');
    // This-week rate 50% vs baseline ~4.5% → ratio > 3× → severity: high
    expect($signal->severity)->toBe('high');
    expect($signal->actionability)->toBe(0.7);
    expect($signal->recencyMinutes)->toBe(60);
    expect($signal->payload)->toHaveKey('operator_name', 'Test Operator');
    expect($signal->payload)->toHaveKey('wagons_this_week', 10);
    expect($signal->payload['this_week_rate'])->toBeFloat();
    expect($signal->payload['baseline_rate'])->toBeFloat();
    expect($signal->payload['this_week_rate'])->toBeGreaterThan($signal->payload['baseline_rate']);
});

// ---------------------------------------------------------------------------
// 7.4 — force_majeure
// ---------------------------------------------------------------------------

it('emits force majeure candidate signal via matcher', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    $placementTime = now()->subHours(20);
    $loadingEnd = now()->subHours(8);

    $rake = Rake::factory()->create([
        'siding_id' => $sidingId,
        'state' => 'loading',
        'placement_time' => $placementTime,
        'loading_end_time' => $loadingEnd,
    ]);

    $reconciliation = PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'dispute_candidate' => false,
        'notes' => null,
        'reconciled_at' => now()->subDays(2),
    ]);

    $downtimeStart = $placementTime->addHours(2);
    $downtimeEvent = LoadriteDowntimeEvent::factory()->create([
        'siding_id' => $sidingId,
        'start_local_time' => $downtimeStart,
        'end_local_time' => $downtimeStart->addMinutes(60),
        'duration_minutes' => 60,
        'reason_name' => 'Plant Stoppage',
    ]);

    // Use the real matcher — bind the concrete class to the contract so the container resolves it.
    app()->bind(DowntimePenaltyMatcherContract::class, App\Services\ForceMajeure\DowntimePenaltyMatcher::class);
    $action = app(CollectSignals::class);
    $signals = $action->handle($sidingId);

    $fmSignals = array_filter($signals, fn (Signal $s) => $s->type === 'force_majeure');
    $fmSignals = array_values($fmSignals);

    expect($fmSignals)->toHaveCount(1);

    $signal = $fmSignals[0];

    expect($signal->type)->toBe('force_majeure');
    expect($signal->severity)->toBe('medium');
    expect($signal->actionability)->toBe(0.6);
    expect($signal->payload)->toHaveKey('rake_id', (int) $rake->id);
    expect($signal->payload)->toHaveKey('reconciliation_id', (int) $reconciliation->id);
    expect($signal->payload['overlap_minutes'])->toBeGreaterThanOrEqual(30);
    expect($signal->payload)->toHaveKey('reason');
    // rs_at_stake = (overlap_minutes / 60) × rs_per_hour (demurrage rate, default 5000).
    // The old formula (overlap_minutes × 1000) was dimensionally wrong (minutes × Rs/MT).
    $expectedRs = round(($signal->payload['overlap_minutes'] / 60.0) * 5000.0, 2);
    expect($signal->rsAtStake)->toBe($expectedRs);
});

// ---------------------------------------------------------------------------
// 7.5 — scale_silence
// ---------------------------------------------------------------------------

it('emits scale silence signal when active rake and scale idle 2h', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    // Active rake (must have state = 'loading' or 'placed') so the silence guard is satisfied.
    Rake::factory()->create([
        'siding_id' => $sidingId,
        'state' => 'loading',
        'placement_time' => now()->subHours(4),
        'loading_start_time' => now()->subHours(3),
    ]);

    // Scale last event was 3 hours ago → gap = 180 min → triggers signal.
    LoadriteEvent::create([
        'event_id' => Str::uuid()->toString(),
        'siding_id' => $sidingId,
        'wagon_sequence' => 1,
        'event_type' => 'Add',
        'weight_mt' => 65.0,
        'event_time' => now()->subHours(3),
        'scale_id' => 'SCALE-QUIET',
    ]);

    $action = makeCollectSignals(makeMatcher());
    $signals = $action->handle($sidingId);

    $scaleSignals = array_filter($signals, fn (Signal $s) => $s->type === 'scale_silence');
    $scaleSignals = array_values($scaleSignals);

    expect($scaleSignals)->toHaveCount(1);

    $signal = $scaleSignals[0];

    expect($signal->type)->toBe('scale_silence');
    expect($signal->severity)->toBe('high');
    expect($signal->rsAtStake)->toBe(0.0);
    expect($signal->actionability)->toBe(0.5);
    expect($signal->payload)->toHaveKey('scale_id', 'SCALE-QUIET');
    expect($signal->payload['gap_minutes'])->toBeGreaterThan(120);
});

// ---------------------------------------------------------------------------
// 7.6 — pending_override
// ---------------------------------------------------------------------------

it('emits pending override signal when oldest exceeds threshold', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    $rake = Rake::factory()->create([
        'siding_id' => $sidingId,
        'state' => 'loading',
    ]);

    // Override waiting 130 minutes → > 120 threshold → severity: medium.
    LoadingOverride::factory()->create([
        'rake_id' => $rake->id,
        'supervisor_review_at' => null,
        'created_at' => now()->subMinutes(130),
        'updated_at' => now()->subMinutes(130),
    ]);

    $action = makeCollectSignals(makeMatcher());
    $signals = $action->handle($sidingId);

    $overrideSignals = array_filter($signals, fn (Signal $s) => $s->type === 'pending_override');
    $overrideSignals = array_values($overrideSignals);

    expect($overrideSignals)->toHaveCount(1);

    $signal = $overrideSignals[0];

    expect($signal->type)->toBe('pending_override');
    expect($signal->severity)->toBe('medium'); // 130 min > 120, ≤ 480
    expect($signal->rsAtStake)->toBe(0.0);
    expect($signal->actionability)->toBe(0.8);
    expect($signal->payload)->toHaveKey('pending_count', 1);
    expect($signal->payload['oldest_minutes'])->toBeGreaterThan(120);
});

// ---------------------------------------------------------------------------
// 7.7 — underloading_trend
// ---------------------------------------------------------------------------

it('emits underloading trend signal from SidingPerformance', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    $today = CarbonImmutable::today();

    // Prior week: 7 × 1000 MT = 7000 MT
    for ($i = 13; $i >= 7; $i--) {
        SidingPerformance::create([
            'siding_id' => $sidingId,
            'as_of_date' => $today->subDays($i)->toDateString(),
            'closing_stock_mt' => 1000.0,
            'rakes_processed' => 0,
            'total_penalty_amount' => 0,
            'penalty_incidents' => 0,
            'average_demurrage_hours' => 0,
            'overload_incidents' => 0,
        ]);
    }

    // This week: 7 × 500 MT = 3500 MT → 50 % drop → severity: high
    for ($i = 6; $i >= 0; $i--) {
        SidingPerformance::create([
            'siding_id' => $sidingId,
            'as_of_date' => $today->subDays($i)->toDateString(),
            'closing_stock_mt' => 500.0,
            'rakes_processed' => 0,
            'total_penalty_amount' => 0,
            'penalty_incidents' => 0,
            'average_demurrage_hours' => 0,
            'overload_incidents' => 0,
        ]);
    }

    $action = makeCollectSignals(makeMatcher());
    $signals = $action->handle($sidingId);

    $trendSignals = array_filter($signals, fn (Signal $s) => $s->type === 'underloading_trend');
    $trendSignals = array_values($trendSignals);

    expect($trendSignals)->toHaveCount(1);

    $signal = $trendSignals[0];

    expect($signal->type)->toBe('underloading_trend');
    expect($signal->severity)->toBe('high'); // 50% drop ≥ 25%
    expect($signal->actionability)->toBe(0.4);
    expect($signal->recencyMinutes)->toBe(60);
    expect($signal->payload)->toHaveKey('this_week_mt', 3500.0);
    expect($signal->payload)->toHaveKey('prior_week_mt', 7000.0);
    expect($signal->payload['delta_pct'])->toBeLessThan(-10.0);
    expect($signal->rsAtStake)->toBeGreaterThan(0.0);
});

// ---------------------------------------------------------------------------
// 7.8 — demurrage_risk
// ---------------------------------------------------------------------------

it('emits at risk demurrage signal when hours to deadline below threshold', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    // Placed 10 hours ago → 2 hours remain → below 3 h threshold → severity: high
    Rake::factory()->create([
        'siding_id' => $sidingId,
        'state' => 'placed',
        'placement_time' => now()->subHours(10),
    ]);

    $action = makeCollectSignals(makeMatcher());
    $signals = $action->handle($sidingId);

    $demurrageSignals = array_filter($signals, fn (Signal $s) => $s->type === 'demurrage_risk');
    $demurrageSignals = array_values($demurrageSignals);

    expect($demurrageSignals)->toHaveCount(1);

    $signal = $demurrageSignals[0];

    expect($signal->type)->toBe('demurrage_risk');
    expect($signal->severity)->toBe('high'); // 2 h remaining > 1 h
    expect($signal->actionability)->toBe(0.9);
    expect($signal->recencyMinutes)->toBe(5);
    expect($signal->rsAtStake)->toBe(0.0); // not yet overdue
    expect($signal->payload)->toHaveKey('rake_id');
    expect($signal->payload)->toHaveKey('rake_number');
    expect($signal->payload['hours_remaining'])->toBeFloat();
    expect($signal->payload['hours_remaining'])->toBeGreaterThan(0.0);
    expect($signal->payload['hours_overdue'])->toBe(0.0);
});

// ---------------------------------------------------------------------------
// 7.9 — empty siding returns empty array
// ---------------------------------------------------------------------------

it('returns empty array when siding has no data', function (): void {
    $siding = Siding::factory()->create();
    $sidingId = (int) $siding->id;

    $action = makeCollectSignals(makeMatcher());
    $signals = $action->handle($sidingId);

    expect($signals)->toBeArray();
    expect($signals)->toBeEmpty();
});

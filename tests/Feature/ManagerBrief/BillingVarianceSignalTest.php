<?php

declare(strict_types=1);

use App\Actions\ManagerBrief\CollectSignals;
use App\DataTransferObjects\ManagerBrief\ActionCard;
use App\DataTransferObjects\ManagerBrief\Signal;
use App\Models\PenaltyReconciliation;
use App\Models\Rake;
use App\Models\Siding;
use App\Services\ForceMajeure\Contracts\DowntimePenaltyMatcherContract;

function makeCollectSignalsForVariance(): CollectSignals
{
    app()->bind(DowntimePenaltyMatcherContract::class, fn () => new class implements DowntimePenaltyMatcherContract
    {
        public function candidates(int $sidingId, int $lookbackDays = 30, int $minOverlapMinutes = 30): array
        {
            return [];
        }
    });

    return app(CollectSignals::class);
}

/**
 * @param  list<Signal>  $signals
 * @return list<Signal>
 */
function onlyBillingVariance(array $signals): array
{
    return array_values(array_filter($signals, fn (Signal $s): bool => $s->type === 'billing_variance'));
}

it('emits a billing_variance signal carrying the recoverable rupees', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id, 'rake_number' => 'RK-9001']);

    PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'predicted_amount' => 40000,
        'billed_amount' => 150000,
        'variance' => 110000,
        'variance_pct' => 275,
        'dispute_candidate' => true,
        'reconciled_at' => now()->subDay(),
    ]);

    $signals = onlyBillingVariance(makeCollectSignalsForVariance()->handle((int) $siding->id));

    expect($signals)->toHaveCount(1);

    $signal = $signals[0];
    expect($signal->rsAtStake)->toBe(110000.0)
        ->and($signal->severity)->toBe('high')
        ->and($signal->payload['penalty_code'])->toBe('DEM')
        ->and($signal->payload['recoverable_rs'])->toBe(110000.0)
        ->and($signal->payload['rake_number'])->toBe('RK-9001');
});

it('ignores reconciliations that are not dispute candidates, are stale, or favour us', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id]);

    // Billed below prediction — nothing to recover.
    PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'predicted_amount' => 50000,
        'billed_amount' => 10000,
        'variance' => -40000,
        'dispute_candidate' => true,
        'reconciled_at' => now(),
    ]);

    // Over-billed but not flagged.
    PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'PLO',
        'predicted_amount' => 1000,
        'billed_amount' => 2000,
        'variance' => 1000,
        'dispute_candidate' => false,
        'reconciled_at' => now(),
    ]);

    // Flagged but outside the 30-day window.
    PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'POL1',
        'predicted_amount' => 1000,
        'billed_amount' => 90000,
        'variance' => 89000,
        'dispute_candidate' => true,
        'reconciled_at' => now()->subMonths(3),
    ]);

    expect(onlyBillingVariance(makeCollectSignalsForVariance()->handle((int) $siding->id)))->toBeEmpty();
});

it('keeps the prevention step on an action card and tolerates its absence', function (): void {
    $withPrevention = ActionCard::fromArray([
        'severity' => 'high',
        'title' => 'Strip overload on RK-9001',
        'why' => 'Wagon 14 is 2.4 MT over PCC.',
        'rs_at_stake' => 2400.0,
        'deep_link' => '/dashboard',
        'deadline' => null,
        'prevention' => 'Strip 2.4 MT from wagon 14 before the RR is raised, saves Rs 2,400.',
    ]);

    expect($withPrevention->prevention)->toBe('Strip 2.4 MT from wagon 14 before the RR is raised, saves Rs 2,400.')
        ->and($withPrevention->toArray()['prevention'])->toBe($withPrevention->prevention);

    // Cards cached before the field existed must still hydrate.
    $legacy = ActionCard::fromArray([
        'severity' => 'low',
        'title' => 'Old cached card',
        'why' => 'No prevention key was stored.',
        'rs_at_stake' => 0.0,
        'deep_link' => '/dashboard',
        'deadline' => null,
    ]);

    expect($legacy->prevention)->toBeNull();
});

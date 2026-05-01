<?php

declare(strict_types=1);

use App\Actions\ReconcilePenaltyHeadsAction;
use App\Models\AppliedPenalty;
use App\Models\PenaltyReconciliation;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RrPenaltySnapshot;

beforeEach(function (): void {
    $this->action = resolve(ReconcilePenaltyHeadsAction::class);

    PenaltyType::factory()->create(['code' => 'DEM', 'is_active' => true]);
    PenaltyType::factory()->create(['code' => 'POL1', 'is_active' => true]);
});

it('creates a reconciliation row when predicted and billed both exist for the same head', function (): void {
    $rake = Rake::factory()->create();
    $type = PenaltyType::query()->where('code', 'DEM')->firstOrFail();

    AppliedPenalty::factory()->for($rake)->for($type, 'penaltyType')->create([
        'amount' => 10000.00,
    ]);
    RrPenaltySnapshot::factory()->for($rake)->create([
        'penalty_code' => 'DEM',
        'amount' => 10500.00,
    ]);

    $outcome = $this->action->handle($rake);

    expect($outcome->totalAffected())->toBe(1);

    $row = PenaltyReconciliation::query()->sole();
    expect($row->penalty_code)->toBe('DEM')
        ->and((float) $row->predicted_amount)->toBe(10000.00)
        ->and((float) $row->billed_amount)->toBe(10500.00)
        ->and((float) $row->variance)->toBe(500.00)
        ->and((float) $row->variance_pct)->toBe(5.00)
        ->and($row->dispute_candidate)->toBeFalse();
});

it('flags dispute candidate when billed exceeds predicted by more than 15 percent', function (): void {
    $rake = Rake::factory()->create();
    $type = PenaltyType::query()->where('code', 'DEM')->firstOrFail();

    AppliedPenalty::factory()->for($rake)->for($type, 'penaltyType')->create(['amount' => 10000.00]);
    RrPenaltySnapshot::factory()->for($rake)->create([
        'penalty_code' => 'DEM',
        'amount' => 12000.00, // 20% over predicted
    ]);

    $this->action->handle($rake);

    $row = PenaltyReconciliation::query()->sole();
    expect($row->dispute_candidate)->toBeTrue();
});

it('flags dispute candidate when railway bills a head that was never predicted', function (): void {
    $rake = Rake::factory()->create();
    RrPenaltySnapshot::factory()->for($rake)->create([
        'penalty_code' => 'PLO',
        'amount' => 5000.00,
    ]);

    $this->action->handle($rake);

    $row = PenaltyReconciliation::query()->where('penalty_code', 'PLO')->sole();
    expect($row->predicted_amount)->toBeNull()
        ->and((float) $row->billed_amount)->toBe(5000.00)
        ->and($row->dispute_candidate)->toBeTrue();
});

it('records predicted-only rows without flagging them as dispute candidates', function (): void {
    $rake = Rake::factory()->create();
    $type = PenaltyType::query()->where('code', 'POL1')->firstOrFail();

    AppliedPenalty::factory()->for($rake)->for($type, 'penaltyType')->create(['amount' => 1500.00]);

    $this->action->handle($rake);

    $row = PenaltyReconciliation::query()->where('penalty_code', 'POL1')->sole();
    expect((float) $row->predicted_amount)->toBe(1500.00)
        ->and($row->billed_amount)->toBeNull()
        ->and($row->dispute_candidate)->toBeFalse();
});

it('is idempotent — running twice updates the same row instead of creating duplicates', function (): void {
    $rake = Rake::factory()->create();
    $type = PenaltyType::query()->where('code', 'DEM')->firstOrFail();
    AppliedPenalty::factory()->for($rake)->for($type, 'penaltyType')->create(['amount' => 1000.00]);

    $this->action->handle($rake);
    $this->action->handle($rake);

    expect(PenaltyReconciliation::query()->count())->toBe(1);
});

it('handles multiple heads on the same rake in one call', function (): void {
    $rake = Rake::factory()->create();
    $dem = PenaltyType::query()->where('code', 'DEM')->firstOrFail();
    $pol1 = PenaltyType::query()->where('code', 'POL1')->firstOrFail();

    AppliedPenalty::factory()->for($rake)->for($dem, 'penaltyType')->create(['amount' => 5000.00]);
    AppliedPenalty::factory()->for($rake)->for($pol1, 'penaltyType')->create(['amount' => 1500.00]);
    RrPenaltySnapshot::factory()->for($rake)->create(['penalty_code' => 'DEM', 'amount' => 5500.00]);
    RrPenaltySnapshot::factory()->for($rake)->create(['penalty_code' => 'POL1', 'amount' => 1500.00]);

    $outcome = $this->action->handle($rake);

    expect($outcome->totalAffected())->toBe(2)
        ->and(PenaltyReconciliation::query()->where('rake_id', $rake->id)->count())->toBe(2);
});

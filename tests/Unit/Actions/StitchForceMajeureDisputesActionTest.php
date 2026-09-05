<?php

declare(strict_types=1);

use App\Actions\StitchForceMajeureDisputesAction;
use App\Models\LoadriteDowntimeEvent;
use App\Models\PenaltyReconciliation;
use App\Models\Rake;
use App\Models\Siding;

beforeEach(function (): void {
    $this->siding = Siding::factory()->create();
    $this->action = app(StitchForceMajeureDisputesAction::class);
});

it('flags reconciliation when downtime overlaps loading window > 15 min', function (): void {
    $rake = Rake::factory()->create([
        'siding_id' => $this->siding->id,
        'placement_time' => '2026-04-30 08:00:00',
        'loading_end_time' => '2026-04-30 12:00:00',
    ]);

    $reconciliation = PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'billed_amount' => 25000,
        'predicted_amount' => 5000,
        'dispute_candidate' => false,
    ]);

    LoadriteDowntimeEvent::factory()->create([
        'siding_id' => $this->siding->id,
        'start_local_time' => '2026-04-30 09:00:00',
        'end_local_time' => '2026-04-30 09:45:00',
        'duration_minutes' => 45,
        'reason_name' => 'Plant Stoppage',
    ]);

    $outcome = $this->action->handle();

    $reconciliation->refresh();
    expect($outcome->candidates)->toHaveCount(1)
        ->and($reconciliation->dispute_candidate)->toBeTrue()
        ->and($reconciliation->notes)->toMatchArray([
            'force_majeure' => [
                'overlap_minutes' => 45,
                'reason' => 'Plant Stoppage',
            ],
        ]);
});

it('does not flag when overlap is below 15 minutes', function (): void {
    $rake = Rake::factory()->create([
        'siding_id' => $this->siding->id,
        'placement_time' => '2026-04-30 08:00:00',
        'loading_end_time' => '2026-04-30 12:00:00',
    ]);

    $reconciliation = PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'dispute_candidate' => false,
    ]);

    LoadriteDowntimeEvent::factory()->create([
        'siding_id' => $this->siding->id,
        'start_local_time' => '2026-04-30 09:00:00',
        'end_local_time' => '2026-04-30 09:10:00',
        'duration_minutes' => 10,
    ]);

    $outcome = $this->action->handle();

    $reconciliation->refresh();
    expect($outcome->candidates)->toHaveCount(0)
        ->and($reconciliation->dispute_candidate)->toBeFalse();
});

it('only considers DEM heads', function (): void {
    $rake = Rake::factory()->create([
        'siding_id' => $this->siding->id,
        'placement_time' => '2026-04-30 08:00:00',
        'loading_end_time' => '2026-04-30 12:00:00',
    ]);

    $reconciliation = PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'PLO',
        'dispute_candidate' => false,
    ]);

    LoadriteDowntimeEvent::factory()->create([
        'siding_id' => $this->siding->id,
        'start_local_time' => '2026-04-30 09:00:00',
        'end_local_time' => '2026-04-30 09:45:00',
        'duration_minutes' => 45,
    ]);

    $this->action->handle();

    expect($reconciliation->refresh()->dispute_candidate)->toBeFalse();
});

it('is idempotent — re-running does not duplicate notes', function (): void {
    $rake = Rake::factory()->create([
        'siding_id' => $this->siding->id,
        'placement_time' => '2026-04-30 08:00:00',
        'loading_end_time' => '2026-04-30 12:00:00',
    ]);

    PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'dispute_candidate' => false,
    ]);

    LoadriteDowntimeEvent::factory()->create([
        'siding_id' => $this->siding->id,
        'start_local_time' => '2026-04-30 09:00:00',
        'end_local_time' => '2026-04-30 09:45:00',
        'duration_minutes' => 45,
        'reason_name' => 'Plant Stoppage',
    ]);

    $first = $this->action->handle();
    $second = $this->action->handle();

    expect($first->candidates)->toHaveCount(1)
        ->and($second->candidates)->toHaveCount(0);
});

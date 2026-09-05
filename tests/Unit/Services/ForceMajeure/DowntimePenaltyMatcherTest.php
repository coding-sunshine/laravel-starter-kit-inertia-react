<?php

declare(strict_types=1);

use App\Models\LoadriteDowntimeEvent;
use App\Models\PenaltyReconciliation;
use App\Models\Rake;
use App\Models\Siding;
use App\Services\ForceMajeure\DowntimePenaltyMatcher;

beforeEach(function (): void {
    $this->siding = Siding::factory()->create();
    $this->matcher = new DowntimePenaltyMatcher;
});

it('returns candidates for unreconciled downtime overlapping penalties', function (): void {
    $rake = Rake::factory()->create([
        'siding_id' => $this->siding->id,
        'placement_time' => '2026-04-30 08:00:00',
        'loading_end_time' => '2026-04-30 12:00:00',
    ]);

    PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'dispute_candidate' => false,
        'notes' => null,
        'reconciled_at' => now()->subDays(5),
    ]);

    LoadriteDowntimeEvent::factory()->create([
        'siding_id' => $this->siding->id,
        'start_local_time' => '2026-04-30 09:00:00',
        'end_local_time' => '2026-04-30 09:45:00',
        'duration_minutes' => 45,
        'reason_name' => 'Plant Stoppage',
    ]);

    $candidates = $this->matcher->candidates($this->siding->id);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]['rake_id'])->toBe($rake->id)
        ->and($candidates[0]['overlap_minutes'])->toBe(45);
});

it('excludes already reconciled penalties', function (): void {
    $rake = Rake::factory()->create([
        'siding_id' => $this->siding->id,
        'placement_time' => '2026-04-30 08:00:00',
        'loading_end_time' => '2026-04-30 12:00:00',
    ]);

    PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'dispute_candidate' => true,
        'notes' => ['force_majeure' => ['overlap_minutes' => 45, 'reason' => 'Plant Stoppage']],
        'reconciled_at' => now()->subDays(5),
    ]);

    LoadriteDowntimeEvent::factory()->create([
        'siding_id' => $this->siding->id,
        'start_local_time' => '2026-04-30 09:00:00',
        'end_local_time' => '2026-04-30 09:45:00',
        'duration_minutes' => 45,
        'reason_name' => 'Plant Stoppage',
    ]);

    $candidates = $this->matcher->candidates($this->siding->id);

    expect($candidates)->toBeEmpty();
});

it('requires 30-minute minimum downtime', function (): void {
    $rake = Rake::factory()->create([
        'siding_id' => $this->siding->id,
        'placement_time' => '2026-04-30 08:00:00',
        'loading_end_time' => '2026-04-30 12:00:00',
    ]);

    PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'dispute_candidate' => false,
        'notes' => null,
        'reconciled_at' => now()->subDays(5),
    ]);

    // 28-minute downtime window
    LoadriteDowntimeEvent::factory()->create([
        'siding_id' => $this->siding->id,
        'start_local_time' => '2026-04-30 09:00:00',
        'end_local_time' => '2026-04-30 09:28:00',
        'duration_minutes' => 28,
        'reason_name' => 'Plant Stoppage',
    ]);

    $candidates = $this->matcher->candidates($this->siding->id);

    expect($candidates)->toBeEmpty();
});

it('filters by siding', function (): void {
    $sidingA = $this->siding;
    $sidingB = Siding::factory()->create();

    $rakeA = Rake::factory()->create([
        'siding_id' => $sidingA->id,
        'placement_time' => '2026-04-30 08:00:00',
        'loading_end_time' => '2026-04-30 12:00:00',
    ]);

    PenaltyReconciliation::factory()->create([
        'rake_id' => $rakeA->id,
        'penalty_code' => 'DEM',
        'dispute_candidate' => false,
        'notes' => null,
        'reconciled_at' => now()->subDays(5),
    ]);

    LoadriteDowntimeEvent::factory()->create([
        'siding_id' => $sidingA->id,
        'start_local_time' => '2026-04-30 09:00:00',
        'end_local_time' => '2026-04-30 09:45:00',
        'duration_minutes' => 45,
        'reason_name' => 'Plant Stoppage',
    ]);

    // Query for siding B — should be empty
    $candidates = $this->matcher->candidates($sidingB->id);

    expect($candidates)->toBeEmpty();
});

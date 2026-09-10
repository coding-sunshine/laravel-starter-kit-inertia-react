<?php

declare(strict_types=1);

use App\Models\LoadriteDowntimeEvent;
use App\Models\PenaltyReconciliation;
use App\Models\Rake;
use App\Models\Siding;

it('runs the stitcher and reports candidate count', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create([
        'siding_id' => $siding->id,
        'placement_time' => '2026-04-30 08:00:00',
        'loading_end_time' => '2026-04-30 12:00:00',
    ]);

    PenaltyReconciliation::factory()->create([
        'rake_id' => $rake->id,
        'penalty_code' => 'DEM',
        'dispute_candidate' => false,
    ]);

    LoadriteDowntimeEvent::factory()->create([
        'siding_id' => $siding->id,
        'start_local_time' => '2026-04-30 09:00:00',
        'end_local_time' => '2026-04-30 09:30:00',
        'duration_minutes' => 30,
    ]);

    $this->artisan('disputes:stitch-force-majeure')
        ->expectsOutputToContain('Flagged 1 dispute candidate')
        ->assertSuccessful();
});

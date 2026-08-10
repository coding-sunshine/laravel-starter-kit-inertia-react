<?php

declare(strict_types=1);

use App\Actions\RunReportAction;
use App\Models\AppliedPenalty;
use App\Models\Rake;
use App\Models\RrPenaltySnapshot;
use App\Models\Siding;

it('drops predicted penalties for rakes that already have RR snapshots', function (): void {
    $siding = Siding::factory()->create();

    $settledRake = Rake::factory()->create(['siding_id' => $siding->id]);
    AppliedPenalty::factory()->create(['rake_id' => $settledRake->id, 'amount' => 9000.00]);
    RrPenaltySnapshot::factory()->create(['rake_id' => $settledRake->id, 'amount' => 1000.00]);

    $pendingRake = Rake::factory()->create(['siding_id' => $siding->id]);
    AppliedPenalty::factory()->create(['rake_id' => $pendingRake->id, 'amount' => 500.00]);

    $rows = (new RunReportAction)->handle('penalty_register', [$siding->id], ['no_limit' => true]);

    // The settled rake contributes only its actual (RR) penalty; the pending
    // rake still shows its prediction because no RR has arrived.
    expect($rows)->toHaveCount(2)
        ->and(collect($rows)->sum(fn (array $row): float => (float) $row['Amount']))->toBe(1500.00);
});

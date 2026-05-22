<?php

declare(strict_types=1);

use App\Models\LoadingOverride;
use App\Models\Rake;
use App\Models\Siding;
use App\Services\ForceMajeure\Contracts\DowntimePenaltyMatcherContract;
use App\Services\ManagerBrief\PendingQueue;
use Carbon\Carbon;

/**
 * Build a PendingQueue with a fixed candidates stub.
 * DowntimePenaltyMatcher is final readonly, so we implement the contract instead.
 *
 * @param  list<array<string,mixed>>  $candidates
 */
function makePendingQueue(array $candidates = []): PendingQueue
{
    $stub = new class($candidates) implements DowntimePenaltyMatcherContract
    {
        /** @param list<array<string,mixed>> $fixed */
        public function __construct(private readonly array $fixed) {}

        public function candidates(int $sidingId, int $lookbackDays = 30, int $minOverlapMinutes = 30): array
        {
            return $this->fixed;
        }
    };

    return new PendingQueue($stub);
}

it('counts overrides awaiting supervisor review', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id]);

    // 2 pending (no supervisor review) + 1 reviewed
    LoadingOverride::factory()->count(2)->create([
        'rake_id' => $rake->id,
        'supervisor_review_at' => null,
    ]);
    LoadingOverride::factory()->create([
        'rake_id' => $rake->id,
        'supervisor_review_at' => now(),
    ]);

    $result = makePendingQueue()->handle((int) $siding->id);

    expect($result['overrides_pending'])->toBe(2);
});

it('counts disputes ready to file via DowntimePenaltyMatcher', function (): void {
    $siding = Siding::factory()->create();

    $candidates = [
        ['rake_id' => 1, 'reconciliation_id' => 1, 'downtime_event_id' => 1, 'overlap_minutes' => 45, 'reason' => 'Equipment downtime', 'reasons_all' => ['Equipment downtime'], 'event_ids' => [1]],
        ['rake_id' => 2, 'reconciliation_id' => 2, 'downtime_event_id' => 2, 'overlap_minutes' => 60, 'reason' => 'Equipment downtime', 'reasons_all' => ['Equipment downtime'], 'event_ids' => [2]],
    ];

    $result = makePendingQueue($candidates)->handle((int) $siding->id);

    expect($result['disputes_ready'])->toBe(2);
});

it('returns zero counts when nothing pending', function (): void {
    $siding = Siding::factory()->create();

    $result = makePendingQueue()->handle((int) $siding->id);

    expect($result['overrides_pending'])->toBe(0);
    expect($result['overrides_oldest_minutes'])->toBeNull();
    expect($result['disputes_ready'])->toBe(0);
    expect($result['disputes_estimated_rs'])->toBe(0.0);
});

it('returns oldest pending override age in minutes', function (): void {
    $siding = Siding::factory()->create();
    $rake = Rake::factory()->create(['siding_id' => $siding->id]);

    // Oldest: 90 minutes ago
    LoadingOverride::factory()->create([
        'rake_id' => $rake->id,
        'supervisor_review_at' => null,
        'created_at' => Carbon::now()->subMinutes(90),
        'updated_at' => Carbon::now()->subMinutes(90),
    ]);

    // More recent: 30 minutes ago
    LoadingOverride::factory()->create([
        'rake_id' => $rake->id,
        'supervisor_review_at' => null,
        'created_at' => Carbon::now()->subMinutes(30),
        'updated_at' => Carbon::now()->subMinutes(30),
    ]);

    $result = makePendingQueue()->handle((int) $siding->id);

    expect($result['overrides_oldest_minutes'])->toBe(90);
});

it('excludes overrides from other sidings', function (): void {
    $siding = Siding::factory()->create();
    $otherSiding = Siding::factory()->create();
    $otherRake = Rake::factory()->create(['siding_id' => $otherSiding->id]);

    // Overrides on a different siding's rake — must not be counted
    LoadingOverride::factory()->count(3)->create([
        'rake_id' => $otherRake->id,
        'supervisor_review_at' => null,
    ]);

    $result = makePendingQueue()->handle((int) $siding->id);

    expect($result['overrides_pending'])->toBe(0);
});

it('computes disputes_estimated_rs from overlap minutes using demurrage rate', function (): void {
    $siding = Siding::factory()->create();

    // Formula: (overlap_minutes / 60) × rs_per_hour (default 5000).
    // 45 min → (45/60) × 5000 = 3750.00
    // 60 min → (60/60) × 5000 = 5000.00
    // Total: 8750.00
    // The old formula (overlap_minutes × rs_per_mt=1000) was dimensionally wrong.
    $candidates = [
        ['rake_id' => 1, 'reconciliation_id' => 1, 'downtime_event_id' => 1, 'overlap_minutes' => 45, 'reason' => 'Equipment downtime', 'reasons_all' => ['Equipment downtime'], 'event_ids' => [1]],
        ['rake_id' => 2, 'reconciliation_id' => 2, 'downtime_event_id' => 2, 'overlap_minutes' => 60, 'reason' => 'Equipment downtime', 'reasons_all' => ['Equipment downtime'], 'event_ids' => [2]],
    ];

    $result = makePendingQueue($candidates)->handle((int) $siding->id);

    expect($result['disputes_estimated_rs'])->toBe(8750.0);
});

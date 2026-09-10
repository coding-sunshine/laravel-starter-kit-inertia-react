<?php

declare(strict_types=1);

use App\Events\AppliedPenaltyPersisted;
use App\Events\RrPenaltySnapshotsImported;
use App\Jobs\ReconcilePenaltyHeadsJob;
use App\Models\Rake;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Queue;

it('queues a ReconcilePenaltyHeadsJob when AppliedPenaltyPersisted fires', function (): void {
    Queue::fake();
    $rake = Rake::factory()->create();

    AppliedPenaltyPersisted::dispatch($rake, 'demurrage');

    Queue::assertPushed(ReconcilePenaltyHeadsJob::class, fn ($job): bool => $job->rake->is($rake));
});

it('queues a ReconcilePenaltyHeadsJob when RrPenaltySnapshotsImported fires', function (): void {
    Queue::fake();
    $rake = Rake::factory()->create();

    RrPenaltySnapshotsImported::dispatch($rake);

    Queue::assertPushed(ReconcilePenaltyHeadsJob::class, fn ($job): bool => $job->rake->is($rake));
});

it('uses WithoutOverlapping middleware keyed by rake id to prevent concurrent same-rake reconciliation', function (): void {
    $rake = Rake::factory()->create();
    $job = new ReconcilePenaltyHeadsJob($rake);

    $middleware = $job->middleware();
    expect($middleware)->toHaveCount(1)
        ->and($middleware[0])->toBeInstanceOf(WithoutOverlapping::class);
});

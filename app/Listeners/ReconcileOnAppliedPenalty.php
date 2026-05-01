<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AppliedPenaltyPersisted;
use App\Jobs\ReconcilePenaltyHeadsJob;

final readonly class ReconcileOnAppliedPenalty
{
    public function handle(AppliedPenaltyPersisted $event): void
    {
        ReconcilePenaltyHeadsJob::dispatch($event->rake);
    }
}

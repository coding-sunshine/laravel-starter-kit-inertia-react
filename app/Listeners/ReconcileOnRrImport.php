<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RrPenaltySnapshotsImported;
use App\Jobs\ReconcilePenaltyHeadsJob;

final readonly class ReconcileOnRrImport
{
    public function handle(RrPenaltySnapshotsImported $event): void
    {
        ReconcilePenaltyHeadsJob::dispatch($event->rake);
    }
}

<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Rake;
use Illuminate\Foundation\Events\Dispatchable;

final class RrPenaltySnapshotsImported
{
    use Dispatchable;

    /**
     * Fired after rr_penalty_snapshots rows have been written for a rake from
     * an RR document import (live or historical).
     */
    public function __construct(public Rake $rake) {}
}

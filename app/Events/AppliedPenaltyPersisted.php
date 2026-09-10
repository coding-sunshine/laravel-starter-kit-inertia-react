<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Rake;
use Illuminate\Foundation\Events\Dispatchable;

final class AppliedPenaltyPersisted
{
    use Dispatchable;

    /**
     * Fired after one or more AppliedPenalty rows have been written/updated for a rake.
     * Source identifies which calculator path produced them (demurrage, weighment, plo).
     */
    public function __construct(
        public Rake $rake,
        public string $source,
    ) {}
}

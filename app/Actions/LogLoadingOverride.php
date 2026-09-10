<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\LoadingOverride;
use App\Models\Rake;
use App\Models\User;

final readonly class LogLoadingOverride
{
    public function handle(
        Rake $rake,
        User $operator,
        string $reason,
        float $overloadMt,
        float $estimatedPenaltyRs,
        ?string $notes = null,
        ?int $wagonLoadingId = null,
    ): LoadingOverride {
        return LoadingOverride::create([
            'rake_id' => $rake->id,
            'wagon_loading_id' => $wagonLoadingId,
            'operator_id' => $operator->id,
            'reason' => $reason,
            'notes' => $notes,
            'overload_mt' => $overloadMt,
            'estimated_penalty_at_time' => $estimatedPenaltyRs,
        ]);
    }
}

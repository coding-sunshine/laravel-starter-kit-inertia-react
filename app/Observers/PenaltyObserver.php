<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\ClassifyPenaltyRootCauseAction;
use App\Models\Penalty;

final class PenaltyObserver
{
    public function created(Penalty $penalty): void
    {
        dispatch(fn () => app(ClassifyPenaltyRootCauseAction::class)->handle($penalty));
    }

    public function updated(Penalty $penalty): void
    {
        if ($penalty->wasChanged(['root_cause', 'description', 'penalty_type'])) {
            dispatch(fn () => app(ClassifyPenaltyRootCauseAction::class)->handle($penalty));
        }
    }
}

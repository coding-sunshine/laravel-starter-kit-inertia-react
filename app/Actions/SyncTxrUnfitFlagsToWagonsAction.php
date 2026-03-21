<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Rake;
use App\Models\Txr;
use App\Models\Wagon;

final readonly class SyncTxrUnfitFlagsToWagonsAction
{
    /**
     * Reset all wagons on the rake to fit, then mark wagons listed in TXR unfit logs as unfit.
     */
    public function handle(Rake $rake, Txr $txr): void
    {
        $unfitWagonIds = $txr->wagonUnfitLogs()->pluck('wagon_id')->all();

        Wagon::query()->where('rake_id', $rake->id)->update(['is_unfit' => false]);

        if ($unfitWagonIds !== []) {
            Wagon::query()->whereIn('id', $unfitWagonIds)->update(['is_unfit' => true]);
        }
    }
}

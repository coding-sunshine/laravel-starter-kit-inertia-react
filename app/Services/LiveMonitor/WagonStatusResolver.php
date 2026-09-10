<?php

declare(strict_types=1);

namespace App\Services\LiveMonitor;

use App\Enums\LiveWagonStatus;
use App\Models\RakeWagonLoading;
use App\Models\Wagon;

/**
 * Derives a {@see LiveWagonStatus} for a wagon based on its current loading row and
 * fitness flag. Pure function — no DB access, no side effects.
 *
 * Source-of-truth precedence (priority order):
 *  1. wagon.is_unfit  → Unfit
 *  2. loaded_qty > pcc_weight  → Overload
 *  3. loaded_qty >= LOADED_THRESHOLD_RATIO × pcc_weight  → Loaded
 *  4. loaded_qty > 0  → Loading
 *  5. otherwise  → Empty
 *
 * The "loaded" threshold uses pcc_weight as the target since that's the per-wagon
 * permissible carrying capacity. cc_capacity_mt on wagon_loading is null in real DB
 * data — do NOT read it.
 */
final class WagonStatusResolver
{
    /**
     * A wagon counts as fully loaded once it reaches this fraction of its
     * permissible carrying capacity. Real Loadrite-completed wagons land at
     * 89–99% of pcc and never reach 100%; a near-100% threshold left every
     * wagon stuck on "loading" forever. 85% captures the real completion band
     * while still leaving clearly part-filled wagons as "loading".
     */
    private const float LOADED_THRESHOLD_RATIO = 0.85;

    /**
     * @param  Wagon  $wagon  must have pcc_weight_mt and is_unfit available
     * @param  RakeWagonLoading|null  $loading  the per-wagon loading row, if any
     */
    public function resolve(Wagon $wagon, ?RakeWagonLoading $loading): LiveWagonStatus
    {
        if ($wagon->is_unfit) {
            return LiveWagonStatus::Unfit;
        }

        $loaded = (float) ($loading?->loaded_quantity_mt ?? 0);
        $pcc = (float) ($wagon->pcc_weight_mt ?? 0);

        if ($loaded <= 0) {
            return LiveWagonStatus::Empty;
        }

        if ($pcc > 0 && $loaded > $pcc) {
            return LiveWagonStatus::Overload;
        }

        if ($pcc > 0 && $loaded >= ($pcc * self::LOADED_THRESHOLD_RATIO)) {
            return LiveWagonStatus::Loaded;
        }

        return LiveWagonStatus::Loading;
    }
}

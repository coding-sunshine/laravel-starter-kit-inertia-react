<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\CommodityUtilisationThreshold;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeWeighment;

/**
 * CalculatePloPenaltyAction
 *
 * Provisional Penal Loading Overcharge calculator per the umbrella spec §5.2.
 * The formula is subject to rewrite once the calibration corpus confirms the
 * actual IR mechanism. The Action's input/output contract stays stable.
 *
 * Compute-only: no DB writes. Use ApplyPloPenaltyAction to persist.
 */
final readonly class CalculatePloPenaltyAction
{
    public function handle(Rake $rake, RakeWeighment $weighment): PloPenaltyResult
    {
        $threshold = CommodityUtilisationThreshold::activeFor((string) ($rake->commodity_grade ?? 'UNGRADED'));
        $utilisation = $threshold !== null ? (float) $threshold->utilisation_threshold : 0.95;

        $weighment->load('rakeWagonWeighments');

        $chargeable = 0.0;
        $loaded = 0.0;
        foreach ($weighment->rakeWagonWeighments as $row) {
            $chargeable += (float) ($row->cc_capacity_mt ?? 0.0) * $utilisation;
            $loaded += (float) ($row->net_weight_mt ?? 0.0);
        }

        $shortfall = max(0.0, $chargeable - $loaded);
        $rate = (float) (PenaltyType::query()->where('code', 'PLO')->value('default_rate') ?? 0.0);
        $amount = round($shortfall * $rate, 2);

        return new PloPenaltyResult(
            rakeId: $rake->id,
            chargeableWeightMt: round($chargeable, 2),
            totalLoadedWeightMt: round($loaded, 2),
            shortfallMt: round($shortfall, 2),
            rate: $rate,
            amount: $amount,
        );
    }
}

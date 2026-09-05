<?php

declare(strict_types=1);

namespace App\Actions;

use App\Events\AppliedPenaltyPersisted;
use App\Models\AppliedPenalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\RakeWeighment;
use Illuminate\Support\Facades\DB;

final readonly class ApplyPloPenaltyAction
{
    public function __construct(private CalculatePloPenaltyAction $calculator) {}

    public function handle(Rake $rake, RakeWeighment $weighment): ?AppliedPenalty
    {
        $result = $this->calculator->handle($rake, $weighment);

        if (! $result->isApplicable()) {
            $this->removeExistingPlo($rake);

            return null;
        }

        $penaltyType = PenaltyType::query()->where('code', 'PLO')->firstOrFail();

        $applied = DB::transaction(function () use ($rake, $weighment, $result, $penaltyType): AppliedPenalty {
            $charge = RakeCharge::query()->firstOrCreate(
                [
                    'rake_id' => $rake->id,
                    'charge_type' => 'PENALTY',
                    'is_actual_charges' => false,
                ],
                [
                    'amount' => 0,
                    'data_source' => 'predicted_penalty',
                    'remarks' => 'Predicted penalty aggregate',
                ],
            );

            $row = AppliedPenalty::query()->updateOrCreate(
                [
                    'rake_id' => $rake->id,
                    'penalty_type_id' => $penaltyType->id,
                    'meta->source' => 'plo',
                ],
                [
                    'rake_charge_id' => $charge->id,
                    'wagon_id' => null,
                    'wagon_number' => null,
                    'quantity' => $result->shortfallMt,
                    'distance' => null,
                    'rate' => $result->rate,
                    'amount' => $result->amount,
                    'meta' => [
                        'source' => 'plo',
                        'rake_weighment_id' => $weighment->id,
                        'chargeable_weight_mt' => $result->chargeableWeightMt,
                        'total_loaded_weight_mt' => $result->totalLoadedWeightMt,
                        'shortfall_mt' => $result->shortfallMt,
                    ],
                ],
            );

            $this->recalculateChargeTotal($charge);

            return $row;
        });

        DB::afterCommit(fn () => AppliedPenaltyPersisted::dispatch($rake, 'plo'));

        return $applied;
    }

    private function removeExistingPlo(Rake $rake): void
    {
        $deleted = AppliedPenalty::query()
            ->where('rake_id', $rake->id)
            ->where('meta->source', 'plo')
            ->delete();

        if ($deleted === 0) {
            return;
        }

        // The aggregate charge still carries the removed PLO amount otherwise.
        $charge = RakeCharge::query()
            ->where('rake_id', $rake->id)
            ->where('charge_type', 'PENALTY')
            ->where('is_actual_charges', false)
            ->first();

        if ($charge !== null) {
            $this->recalculateChargeTotal($charge);
        }
    }

    private function recalculateChargeTotal(RakeCharge $charge): void
    {
        $total = AppliedPenalty::query()
            ->where('rake_charge_id', $charge->id)
            ->sum('amount');
        $charge->update(['amount' => round((float) $total, 2)]);
    }
}

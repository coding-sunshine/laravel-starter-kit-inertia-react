<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AppliedPenalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeWagonWeighment;
use App\Models\RakeWeighment;
use Illuminate\Database\Eloquent\Collection;

final readonly class ApplyWeighmentPenaltiesAction
{
    public function handle(Rake $rake, RakeWeighment $weighment): void
    {
        $penaltyTypes = PenaltyType::query()
            ->whereIn('code', ['POL1', 'POLA'])
            ->where('is_active', true)
            ->get()
            ->keyBy('code');

        if ($penaltyTypes->isEmpty()) {
            return;
        }

        // Remove previous weighment-derived penalties for this rake
        AppliedPenalty::query()
            ->where('rake_id', $rake->id)
            ->where('meta->source', 'weighment')
            ->delete();

        /** @var Collection<int, RakeWagonWeighment> $wagonWeighments */
        $wagonWeighments = $weighment->rakeWagonWeighments()
            ->with('wagon')
            ->get();

        if ($wagonWeighments->isEmpty()) {
            return;
        }

        $this->applyPol1Penalties($rake, $weighment, $wagonWeighments, $penaltyTypes);
        $this->applyPolaPenalty($rake, $weighment, $wagonWeighments, $penaltyTypes);
    }

    /**
     * Create POL1 (individual wagon punitive overloading) penalties.
     *
     * @param  Collection<int, RakeWagonWeighment>  $wagonWeighments
     * @param  Collection<string, PenaltyType>  $penaltyTypes
     */
    private function applyPol1Penalties(
        Rake $rake,
        RakeWeighment $weighment,
        Collection $wagonWeighments,
        Collection $penaltyTypes,
    ): void {
        /** @var PenaltyType|null $pol1Type */
        $pol1Type = $penaltyTypes->get('POL1');

        if (! $pol1Type) {
            return;
        }

        $rate = (float) ($pol1Type->default_rate ?? 0.0);

        foreach ($wagonWeighments as $row) {
            $excessMt = (float) ($row->over_load_mt ?? 0.0);

            if ($excessMt <= 0.0) {
                continue;
            }

            $amount = $rate > 0.0
                ? $rate * $excessMt
                : 0.0;

            AppliedPenalty::query()->create([
                'penalty_type_id' => $pol1Type->id,
                'rake_id' => $rake->id,
                'wagon_id' => $row->wagon_id,
                'wagon_number' => $row->wagon_number ?? $row->wagon?->wagon_number,
                'quantity' => $excessMt,
                'distance' => null,
                'rate' => $rate > 0.0 ? $rate : null,
                'amount' => $amount,
                'meta' => [
                    'source' => 'weighment',
                    'rake_weighment_id' => $weighment->id,
                    'rake_wagon_weighment_id' => $row->id,
                    'overload_mt' => $excessMt,
                ],
            ]);
        }
    }

    /**
     * Create a POLA (average rake punitive overloading) penalty when applicable.
     *
     * @param  Collection<int, RakeWagonWeighment>  $wagonWeighments
     * @param  Collection<string, PenaltyType>  $penaltyTypes
     */
    private function applyPolaPenalty(
        Rake $rake,
        RakeWeighment $weighment,
        Collection $wagonWeighments,
        Collection $penaltyTypes,
    ): void {
        /** @var PenaltyType|null $polaType */
        $polaType = $penaltyTypes->get('POLA');

        if (! $polaType) {
            return;
        }

        $totalNetWeight = (float) ($weighment->total_net_weight_mt ?? 0.0);

        if ($totalNetWeight <= 0.0) {
            return;
        }

        $totalPcc = $wagonWeighments
            ->map(fn (RakeWagonWeighment $row): float => (float) ($row->wagon?->pcc_weight_mt ?? 0.0))
            ->sum();

        if ($totalPcc <= 0.0) {
            return;
        }

        $excessTotalMt = $totalNetWeight - $totalPcc;

        if ($excessTotalMt <= 0.0) {
            return;
        }

        $rate = (float) ($polaType->default_rate ?? 0.0);

        $amount = $rate > 0.0
            ? $rate * $excessTotalMt
            : 0.0;

        $wagonCount = $wagonWeighments->count();
        $averageActual = $wagonCount > 0 ? $totalNetWeight / $wagonCount : null;
        $averagePcc = $wagonCount > 0 ? $totalPcc / $wagonCount : null;

        AppliedPenalty::query()->create([
            'penalty_type_id' => $polaType->id,
            'rake_id' => $rake->id,
            'wagon_id' => null,
            'wagon_number' => null,
            'quantity' => $excessTotalMt,
            'distance' => null,
            'rate' => $rate > 0.0 ? $rate : null,
            'amount' => $amount,
            'meta' => [
                'source' => 'weighment',
                'rake_weighment_id' => $weighment->id,
                'total_net_weight_mt' => $totalNetWeight,
                'total_pcc_mt' => $totalPcc,
                'excess_total_mt' => $excessTotalMt,
                'wagon_count' => $wagonCount,
                'average_actual_mt' => $averageActual,
                'average_pcc_mt' => $averagePcc,
            ],
        ]);
    }
}

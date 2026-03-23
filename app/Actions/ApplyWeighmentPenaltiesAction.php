<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AppliedPenalty;
use App\Models\PenaltyType;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\RakeWagonWeighment;
use App\Models\RakeWeighment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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

        /** @var Collection<int, RakeWagonWeighment> $wagonWeighments */
        $wagonWeighments = $weighment->rakeWagonWeighments()
            ->with('wagon')
            ->get();

        $penaltyRows = [
            ...$this->buildPol1PenaltyRows($rake, $weighment, $wagonWeighments, $penaltyTypes),
            ...$this->buildPolaPenaltyRows($rake, $weighment, $wagonWeighments, $penaltyTypes),
        ];

        DB::transaction(function () use ($rake, $penaltyRows): void {
            AppliedPenalty::query()
                ->where('rake_id', $rake->id)
                ->where('meta->source', 'weighment')
                ->delete();

            $totalAmount = collect($penaltyRows)->sum(static function (array $row): float {
                return (float) ($row['amount'] ?? 0.0);
            });

            $canonicalPenaltyCharge = RakeCharge::query()->updateOrCreate(
                [
                    'rake_id' => $rake->id,
                    'diverrt_destination_id' => null,
                    'charge_type' => 'PENALTY',
                    'is_actual_charges' => false,
                ],
                [
                    'amount' => round($totalAmount, 2),
                    'data_source' => 'predicted_penalty',
                    'remarks' => 'Predicted penalty aggregate from weighment',
                ],
            );

            foreach ($penaltyRows as $row) {
                $row['rake_charge_id'] = $canonicalPenaltyCharge->id;
                AppliedPenalty::query()->create($row);
            }
        });
    }

    /**
     * Build POL1 (individual wagon punitive overloading) penalties.
     *
     * @param  Collection<int, RakeWagonWeighment>  $wagonWeighments
     * @param  Collection<string, PenaltyType>  $penaltyTypes
     * @return list<array<string, mixed>>
     */
    private function buildPol1PenaltyRows(
        Rake $rake,
        RakeWeighment $weighment,
        Collection $wagonWeighments,
        Collection $penaltyTypes,
    ): array {
        /** @var PenaltyType|null $pol1Type */
        $pol1Type = $penaltyTypes->get('POL1');

        if (! $pol1Type) {
            return [];
        }

        $rate = (float) ($pol1Type->default_rate ?? 0.0);
        $rows = [];

        foreach ($wagonWeighments as $row) {
            $excessMt = (float) ($row->over_load_mt ?? 0.0);

            if ($excessMt <= 0.0) {
                continue;
            }

            $amount = $rate > 0.0
                ? $rate * $excessMt
                : 0.0;

            $rows[] = [
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
            ];
        }

        return $rows;
    }

    /**
     * Build a POLA (average rake punitive overloading) penalty when applicable.
     *
     * @param  Collection<int, RakeWagonWeighment>  $wagonWeighments
     * @param  Collection<string, PenaltyType>  $penaltyTypes
     * @return list<array<string, mixed>>
     */
    private function buildPolaPenaltyRows(
        Rake $rake,
        RakeWeighment $weighment,
        Collection $wagonWeighments,
        Collection $penaltyTypes,
    ): array {
        /** @var PenaltyType|null $polaType */
        $polaType = $penaltyTypes->get('POLA');

        if (! $polaType) {
            return [];
        }

        $totalNetWeight = (float) ($weighment->total_net_weight_mt ?? 0.0);

        if ($totalNetWeight <= 0.0) {
            return [];
        }

        $totalPcc = $wagonWeighments
            ->map(fn (RakeWagonWeighment $row): float => (float) ($row->wagon?->pcc_weight_mt ?? 0.0))
            ->sum();

        if ($totalPcc <= 0.0) {
            return [];
        }

        $excessTotalMt = $totalNetWeight - $totalPcc;

        if ($excessTotalMt <= 0.0) {
            return [];
        }

        $rate = (float) ($polaType->default_rate ?? 0.0);

        $amount = $rate > 0.0
            ? $rate * $excessTotalMt
            : 0.0;

        $wagonCount = $wagonWeighments->count();
        $averageActual = $wagonCount > 0 ? $totalNetWeight / $wagonCount : null;
        $averagePcc = $wagonCount > 0 ? $totalPcc / $wagonCount : null;

        return [[
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
        ]];
    }
}

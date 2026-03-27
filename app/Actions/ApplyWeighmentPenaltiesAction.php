<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\NotifySuperAdmins;
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

        if ($penaltyRows === []) {
            return;
        }

        DB::transaction(function () use ($rake, $penaltyRows): void {
            AppliedPenalty::query()
                ->where('rake_id', $rake->id)
                ->where('meta->source', 'weighment')
                ->delete();

            $canonicalPenaltyCharge = RakeCharge::query()->firstOrCreate(
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

            foreach ($penaltyRows as $row) {
                $row['rake_charge_id'] = $canonicalPenaltyCharge->id;
                AppliedPenalty::query()->create($row);
            }

            $total = AppliedPenalty::query()
                ->where('rake_charge_id', $canonicalPenaltyCharge->id)
                ->sum('amount');

            $canonicalPenaltyCharge->update(['amount' => round((float) $total, 2)]);
        });

        $totalAmount = (float) array_sum(array_map(static fn (array $row): float => (float) ($row['amount'] ?? 0), $penaltyRows));
        $byCode = [];
        foreach ($penaltyRows as $row) {
            $code = (string) ($row['meta']['source_code'] ?? '');
            if ($code === '' && isset($row['penalty_type_id'])) {
                $code = ''; // filled below from loaded types when possible
            }
            $byCode[$code] = (float) ($byCode[$code] ?? 0) + (float) ($row['amount'] ?? 0);
        }

        // Best-effort: map penalty_type_id back to code for breakdown
        $typeById = $penaltyTypes->mapWithKeys(static fn (PenaltyType $pt): array => [(int) $pt->id => (string) $pt->code])->all();
        $breakdown = [];
        foreach ($penaltyRows as $row) {
            $pid = (int) ($row['penalty_type_id'] ?? 0);
            $code = $typeById[$pid] ?? '—';
            $breakdown[$code] = (float) ($breakdown[$code] ?? 0) + (float) ($row['amount'] ?? 0);
        }

        DB::afterCommit(function () use ($rake, $totalAmount, $breakdown): void {
            NotifySuperAdmins::dispatch(\App\Notifications\PenaltyCreatedNotification::class, [
                'source' => 'weighment',
                'rake_id' => $rake->id,
                'rake_number' => (string) $rake->rake_number,
                'siding_id' => $rake->siding_id,
                'siding_name' => $rake->siding?->name,
                'amount_total' => round($totalAmount, 2),
                'breakdown' => collect($breakdown)
                    ->map(fn (float $amount, string $code): array => ['code' => $code, 'amount' => round($amount, 2)])
                    ->values()
                    ->all(),
            ]);
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

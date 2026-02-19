<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Rake;
use App\Models\StockLedger;
use App\Models\VehicleUnload;
use Illuminate\Support\Facades\DB;

final readonly class ReconcileRakeAction
{
    private const VARIANCE_MATCH_PCT = 1.0;

    private const VARIANCE_MINOR_PCT = 5.0;

    /**
     * Compute five-point reconciliation for a rake. Returns array of comparison points.
     *
     * @return array<int, array{point: string, value_a: float|null, value_b: float|null, variance_mt: float|null, variance_pct: float|null, status: string}>
     */
    public function handle(Rake $rake): array
    {
        $sidingId = $rake->siding_id;
        $loadingStart = $rake->loading_start_time?->toDateTimeString();
        $loadingEnd = $rake->loading_end_time?->toDateTimeString();

        $loaderTotal = (float) $rake->wagons()->sum('loader_recorded_qty_mt');
        $weighmentTotal = (float) $rake->weighments()->orderByDesc('weighment_time')->value('total_weight_mt');
        if ($weighmentTotal === 0.0) {
            $weighmentTotal = (float) $rake->wagons()->sum('weighment_qty_mt');
        }
        $rrWeight = $rake->rrDocuments()->orderByDesc('rr_received_date')->value('rr_weight_mt');
        $rrWeight = $rrWeight !== null ? (float) $rrWeight : null;
        $ppReceipt = $rake->powerPlantReceipts()->orderByDesc('receipt_date')->first();
        $ppWeight = $ppReceipt ? (float) $ppReceipt->weight_mt : null;

        $points = [];

        $points[] = $this->pointMineVsSiding($sidingId, $loadingStart, $loadingEnd);
        $points[] = $this->pointSidingVsRake($sidingId, $rake, $loadingStart, $weighmentTotal);
        $points[] = $this->pointRakeVsWeighment($loaderTotal, $weighmentTotal);
        $points[] = $this->pointWeighmentVsRr($weighmentTotal, $rrWeight);
        $points[] = $this->pointRrVsPowerPlant($rrWeight, $ppWeight);

        return $points;
    }

    /**
     * @return array{point: string, value_a: float|null, value_b: float|null, variance_mt: float|null, variance_pct: float|null, status: string}
     */
    private function pointMineVsSiding(int $sidingId, ?string $from, ?string $to): array
    {
        $unloadTotal = VehicleUnload::query()
            ->where('siding_id', $sidingId)
            ->when($from, fn ($q) => $q->where('unload_end_time', '>=', $from))
            ->when($to, fn ($q) => $q->where('unload_end_time', '<=', $to))
            ->sum(DB::raw('COALESCE(weighment_weight_mt, mine_weight_mt, 0)'));

        $ledgerReceipts = StockLedger::query()
            ->where('siding_id', $sidingId)
            ->where('transaction_type', 'receipt')
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->sum('quantity_mt');

        $valueA = (float) $unloadTotal;
        $valueB = (float) $ledgerReceipts;

        return $this->compare('Mine vs Siding', $valueA, $valueB);
    }

    /**
     * @return array{point: string, value_a: float|null, value_b: float|null, variance_mt: float|null, variance_pct: float|null, status: string}
     */
    private function pointSidingVsRake(int $sidingId, Rake $rake, ?string $before, float $rakeLoaded): array
    {
        $closing = StockLedger::query()
            ->where('siding_id', $sidingId)
            ->when($before, fn ($q) => $q->where('created_at', '<', $before))
            ->orderByDesc('created_at')
            ->value('closing_balance_mt');
        $valueA = $closing !== null ? (float) $closing : null;
        $valueB = $rakeLoaded > 0 ? $rakeLoaded : null;

        return $this->compare('Siding vs Rake', $valueA ?? 0, $valueB ?? 0);
    }

    /**
     * @return array{point: string, value_a: float|null, value_b: float|null, variance_mt: float|null, variance_pct: float|null, status: string}
     */
    private function pointRakeVsWeighment(float $loaderTotal, float $weighmentTotal): array
    {
        return $this->compare('Rake vs Weighment', $loaderTotal, $weighmentTotal);
    }

    /**
     * @return array{point: string, value_a: float|null, value_b: float|null, variance_mt: float|null, variance_pct: float|null, status: string}
     */
    private function pointWeighmentVsRr(float $weighmentTotal, ?float $rrWeight): array
    {
        $valueA = $weighmentTotal > 0 ? $weighmentTotal : null;
        $valueB = $rrWeight;
        if ($valueA === null && $valueB === null) {
            return ['point' => 'Weighment vs RR', 'value_a' => null, 'value_b' => null, 'variance_mt' => null, 'variance_pct' => null, 'status' => 'PENDING'];
        }

        return $this->compare('Weighment vs RR', $valueA ?? 0, $valueB ?? 0);
    }

    /**
     * @return array{point: string, value_a: float|null, value_b: float|null, variance_mt: float|null, variance_pct: float|null, status: string}
     */
    private function pointRrVsPowerPlant(?float $rrWeight, ?float $ppWeight): array
    {
        if ($rrWeight === null && $ppWeight === null) {
            return ['point' => 'RR vs Power Plant', 'value_a' => null, 'value_b' => null, 'variance_mt' => null, 'variance_pct' => null, 'status' => 'PENDING'];
        }

        return $this->compare('RR vs Power Plant', $rrWeight ?? 0, $ppWeight ?? 0);
    }

    /**
     * @return array{point: string, value_a: float|null, value_b: float|null, variance_mt: float|null, variance_pct: float|null, status: string}
     */
    private function compare(string $point, float $valueA, float $valueB): array
    {
        $varianceMt = $valueA - $valueB;
        $ref = $valueB !== 0.0 ? $valueB : $valueA;
        $variancePct = $ref !== 0.0 ? (abs($varianceMt) / $ref) * 100 : 0.0;
        $status = $variancePct <= self::VARIANCE_MATCH_PCT ? 'MATCH' : ($variancePct <= self::VARIANCE_MINOR_PCT ? 'MINOR_DIFF' : 'MAJOR_DIFF');

        return [
            'point' => $point,
            'value_a' => $valueA,
            'value_b' => $valueB,
            'variance_mt' => $varianceMt,
            'variance_pct' => round($variancePct, 2),
            'status' => $status,
        ];
    }
}

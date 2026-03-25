<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\FreightRateMaster;
use App\Models\PowerPlant;
use App\Models\PowerplantSidingDistance;
use App\Models\Rake;
use App\Models\RakeWeighment;
use Illuminate\Http\JsonResponse;

final class PreRrController extends Controller
{
    private const string DEFAULT_CLASS_CODE = '145A';

    private const float DEFAULT_GST_PERCENT = 5.0;

    public function show(Rake $rake): JsonResponse
    {
        $user = request()->user();
        if (! $user || ! $user->isSuperAdmin()) {
            abort(403);
        }

        return response()->json($this->buildEstimate($rake));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildEstimate(Rake $rake): array
    {
        $latestWeighment = $this->resolveLatestSuccessfulWeighment($rake);
        $actualLoadedWeight = $this->resolveActualLoadedWeight($latestWeighment);
        $sumPccWeight = $this->resolveSumPccWeight($latestWeighment);
        $chargeableWeight = max($actualLoadedWeight, $sumPccWeight);

        $distance = $this->resolveDistanceKm($rake);
        $rate = $this->resolveRatePerMt($distance);

        $hasFreightInputs = $distance !== null && $rate !== null && $chargeableWeight > 0;
        $freightAmount = $hasFreightInputs ? $chargeableWeight * $rate : null;

        $otherCharges = 0.0;
        $penaltyAmount = 0.0;
        $rebateAmount = 0.0;

        $gstAmount = $freightAmount === null
            ? null
            : (($freightAmount + $otherCharges) * (self::DEFAULT_GST_PERCENT / 100));

        $totalAmount = $freightAmount === null || $gstAmount === null
            ? null
            : ($freightAmount + $otherCharges + $gstAmount + $penaltyAmount - $rebateAmount);

        return [
            'available' => $freightAmount !== null,
            'classCode' => self::DEFAULT_CLASS_CODE,
            'distanceKm' => $this->roundOrNull($distance),
            'actualLoadedWeightMt' => $this->roundOrNull($actualLoadedWeight),
            'sumPccWeightMt' => $this->roundOrNull($sumPccWeight),
            'chargeableWeightMt' => $this->roundOrNull($chargeableWeight),
            'ratePerMt' => $this->roundOrNull($rate),
            'freightAmount' => $this->roundOrNull($freightAmount),
            'otherCharges' => $this->roundOrNull($otherCharges),
            'penaltyAmount' => $this->roundOrNull($penaltyAmount),
            'rebateAmount' => $this->roundOrNull($rebateAmount),
            'gstPercent' => self::DEFAULT_GST_PERCENT,
            'gstAmount' => $this->roundOrNull($gstAmount),
            'totalAmount' => $this->roundOrNull($totalAmount),
            'formula' => 'TOTAL = Freight + Other Charges + GST + Penalty - Rebate',
            'warnings' => $this->buildWarnings($distance, $rate, $latestWeighment, $chargeableWeight),
        ];
    }

    private function resolveLatestSuccessfulWeighment(Rake $rake): ?RakeWeighment
    {
        return $rake->rakeWeighments()
            ->where('status', 'success')
            ->orderByDesc('attempt_no')
            ->orderByDesc('gross_weighment_datetime')
            ->with(['rakeWagonWeighments.wagon:id,pcc_weight_mt'])
            ->first();
    }

    private function resolveActualLoadedWeight(?RakeWeighment $weighment): float
    {
        if ($weighment === null) {
            return 0.0;
        }

        $headerNet = (float) ($weighment->total_net_weight_mt ?? 0);
        if ($headerNet > 0) {
            return $headerNet;
        }

        return (float) $weighment->rakeWagonWeighments->sum(
            static fn ($row): float => (float) ($row->net_weight_mt ?? 0)
        );
    }

    private function resolveSumPccWeight(?RakeWeighment $weighment): float
    {
        if ($weighment === null) {
            return 0.0;
        }

        return (float) $weighment->rakeWagonWeighments->sum(function ($row): float {
            $fromCcCapacity = (float) ($row->cc_capacity_mt ?? 0);
            if ($fromCcCapacity > 0) {
                return $fromCcCapacity;
            }

            return (float) ($row->wagon?->pcc_weight_mt ?? 0);
        });
    }

    private function resolveDistanceKm(Rake $rake): ?float
    {
        if ($rake->siding_id === null) {
            return null;
        }

        $destinationKey = mb_strtolower(mb_trim((string) ($rake->destination_code ?? $rake->destination ?? '')));
        if ($destinationKey === '') {
            return null;
        }

        $powerPlant = PowerPlant::query()
            ->whereRaw('LOWER(code) = ?', [$destinationKey])
            ->orWhereRaw('LOWER(name) = ?', [$destinationKey])
            ->orWhereRaw('LOWER(location) = ?', [$destinationKey])
            ->first();

        if (! $powerPlant) {
            return null;
        }

        $distance = PowerplantSidingDistance::query()
            ->where('siding_id', $rake->siding_id)
            ->where('power_plant_id', $powerPlant->id)
            ->value('distance_km');

        return $distance !== null ? (float) $distance : null;
    }

    private function resolveRatePerMt(?float $distanceKm): ?float
    {
        if ($distanceKm === null || $distanceKm <= 0) {
            return null;
        }

        $today = now()->toDateString();
        $row = FreightRateMaster::query()
            ->where('class_code', self::DEFAULT_CLASS_CODE)
            ->where('is_active', true)
            ->where('distance_from_km', '<=', $distanceKm)
            ->where('distance_to_km', '>=', $distanceKm)
            ->whereDate('effective_from', '<=', $today)
            ->where(function ($query) use ($today): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $today);
            })
            ->orderByDesc('effective_from')
            ->first();

        return $row !== null ? (float) $row->rate_per_mt : null;
    }

    /**
     * @return list<string>
     */
    private function buildWarnings(?float $distance, ?float $rate, ?RakeWeighment $weighment, float $chargeableWeight): array
    {
        $warnings = [];

        if ($weighment === null) {
            $warnings[] = 'Weighment not available.';
        }

        if ($chargeableWeight <= 0) {
            $warnings[] = 'Chargeable weight cannot be computed.';
        }

        if ($distance === null) {
            $warnings[] = 'Distance mapping missing for this siding and destination.';
        }

        if ($distance !== null && $rate === null) {
            $warnings[] = 'No active 145A slab found for computed distance.';
        }

        return $warnings;
    }

    private function roundOrNull(?float $value): ?float
    {
        return $value === null ? null : round($value, 2);
    }
}

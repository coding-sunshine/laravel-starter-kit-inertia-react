<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\AppliedPenalty;
use App\Models\FreightRateMaster;
use App\Models\PenaltyType;
use App\Models\PowerPlant;
use App\Models\PowerplantSidingDistance;
use App\Models\Rake;
use App\Models\RakeCharge;
use App\Models\RakeWeighment;
use App\Models\SectionTimer;
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

        $charges = $this->resolveRakeCharges($rake);
        $otherCharges = $charges['OTHER_CHARGE'];
        $penaltyAmount = $charges['PENALTY'];

        $demurrage = $this->resolveDemurrage($rake);

        $subtotal = $freightAmount !== null
            ? ($freightAmount + $otherCharges + $penaltyAmount)
            : null;

        $gstAmount = $subtotal !== null
            ? ($subtotal * (self::DEFAULT_GST_PERCENT / 100))
            : null;

        $totalAmount = $subtotal !== null && $gstAmount !== null
            ? ($subtotal + $gstAmount)
            : null;

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
            'penalties' => $this->resolvePenaltyBreakdown($rake),
            'gstPercent' => self::DEFAULT_GST_PERCENT,
            'gstAmount' => $this->roundOrNull($gstAmount),
            'totalAmount' => $this->roundOrNull($totalAmount),
            'demurrage' => $demurrage,
            'formula' => 'TOTAL = (Freight + OTC + Penalty) + 5% GST',
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
     * @return array{PENALTY: float, OTHER_CHARGE: float}
     */
    private function resolveRakeCharges(Rake $rake): array
    {
        $charges = RakeCharge::query()
            ->where('rake_id', $rake->id)
            ->where('is_actual_charges', false)
            ->whereIn('charge_type', ['PENALTY', 'OTHER_CHARGE'])
            ->pluck('amount', 'charge_type');

        return [
            'PENALTY' => round((float) ($charges['PENALTY'] ?? 0), 2),
            'OTHER_CHARGE' => round((float) ($charges['OTHER_CHARGE'] ?? 0), 2),
        ];
    }

    /**
     * @return list<array{code: string, name: string, amount: float, breakdown: string}>
     */
    private function resolvePenaltyBreakdown(Rake $rake): array
    {
        $penalties = AppliedPenalty::query()
            ->where('rake_id', $rake->id)
            ->with('penaltyType:id,code,name,calculation_type')
            ->get();

        return $penalties->map(function (AppliedPenalty $ap): array {
            $code = $ap->penaltyType?->code ?? '-';
            $name = $ap->penaltyType?->name ?? 'Unknown';
            $amount = round((float) $ap->amount, 2);
            $meta = $ap->meta ?? [];

            $breakdown = match ($ap->penaltyType?->calculation_type) {
                'per_hour' => sprintf(
                    '%d min loaded, %d min free, %d min excess = %d hr × ₹%s/hr',
                    $meta['total_loading_minutes'] ?? 0,
                    $meta['free_minutes'] ?? 0,
                    $meta['excess_minutes'] ?? 0,
                    $meta['charged_hours'] ?? 0,
                    number_format((float) ($ap->rate ?? 0), 2),
                ),
                default => sprintf(
                    '%s × ₹%s/MT',
                    number_format((float) ($ap->quantity ?? 0), 2),
                    number_format((float) ($ap->rate ?? 0), 2),
                ),
            };

            return [
                'code' => $code,
                'name' => $name,
                'wagonNumber' => $ap->wagon_number ?? null,
                'amount' => $amount,
                'breakdown' => $breakdown,
            ];
        })->values()->all();
    }

    /**
     * @return array{applied: bool, totalLoadingMinutes: int, freeMinutes: int, excessMinutes: int, chargedHours: int, ratePerHour: float, amount: float}
     */
    private function resolveDemurrage(Rake $rake): array
    {
        $empty = [
            'applied' => false,
            'totalLoadingMinutes' => 0,
            'freeMinutes' => 0,
            'excessMinutes' => 0,
            'chargedHours' => 0,
            'ratePerHour' => 0.0,
            'amount' => 0.0,
        ];

        if ($rake->loading_start_time === null || $rake->loading_end_time === null) {
            return $empty;
        }

        $totalLoadingMinutes = (int) $rake->loading_start_time->diffInMinutes($rake->loading_end_time);
        $freeMinutes = (int) (SectionTimer::query()
            ->where('section_name', 'loading')
            ->value('free_minutes') ?? 180);

        $excessMinutes = $totalLoadingMinutes - $freeMinutes;

        if ($excessMinutes <= 0) {
            return array_merge($empty, [
                'totalLoadingMinutes' => $totalLoadingMinutes,
                'freeMinutes' => $freeMinutes,
            ]);
        }

        $penaltyType = PenaltyType::query()
            ->where('code', 'DEM')
            ->where('is_active', true)
            ->first();

        $rate = (float) ($penaltyType?->default_rate ?? 0.0);
        $chargedHours = (int) ceil($excessMinutes / 60);
        $amount = round($chargedHours * $rate, 2);

        return [
            'applied' => true,
            'totalLoadingMinutes' => $totalLoadingMinutes,
            'freeMinutes' => $freeMinutes,
            'excessMinutes' => $excessMinutes,
            'chargedHours' => $chargedHours,
            'ratePerHour' => $rate,
            'amount' => $amount,
        ];
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

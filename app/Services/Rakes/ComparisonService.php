<?php

declare(strict_types=1);

namespace App\Services\Rakes;

use App\Models\Rake;
use Illuminate\Support\Collection;

final readonly class ComparisonService
{
    public function getComparisonData(Rake $rake): Collection
    {
        if (! $this->hasRequiredData($rake)) {
            return collect();
        }

        $wagonLoadings = $rake->wagonLoadings()->with('wagon')->get();
        $weighment = $rake->rakeWeighments()
            ->where('status', 'success')
            ->orderByDesc('attempt_no')
            ->orderByDesc('gross_weighment_datetime')
            ->first();

        if (! $weighment) {
            return collect();
        }

        $wagonWeights = $weighment->wagonWeights()->with('wagon')->get()->keyBy('wagon_id');

        return $wagonLoadings->map(function ($loading) use ($wagonWeights) {
            $weighment = $wagonWeights->get($loading->wagon_id);

            if (! $weighment) {
                return null;
            }

            $loadedQty = (float) $loading->loaded_quantity_mt;
            $netWeight = (float) $weighment->net_weight_mt;
            $difference = $loadedQty - $netWeight;
            $differencePercent = $netWeight > 0 ? ($difference / $netWeight) * 100 : 0;

            $status = 'normal';
            if (abs($differencePercent) > 5) {
                $status = $difference > 0 ? 'overload' : 'underload';
            }

            return [
                'wagon_number' => $loading->wagon->wagon_number,
                'wagon_sequence' => $loading->wagon->wagon_sequence,
                'loaded_quantity_mt' => $loadedQty,
                'net_weight_mt' => $netWeight,
                'difference_mt' => $difference,
                'difference_percent' => $differencePercent,
                'status' => $status,
                'action_taken' => '',
            ];
        })->filter();
    }

    public function getSummary(Rake $rake): array
    {
        $comparisonData = $this->getComparisonData($rake);

        if ($comparisonData->isEmpty()) {
            return [
                'total_loaded' => 0,
                'total_weighed' => 0,
                'total_difference' => 0,
                'normal_count' => 0,
                'overload_count' => 0,
                'underload_count' => 0,
                'has_issues' => false,
            ];
        }

        $totalLoaded = $comparisonData->sum('loaded_quantity_mt');
        $totalWeighed = $comparisonData->sum('net_weight_mt');
        $totalDifference = $totalLoaded - $totalWeighed;

        return [
            'total_loaded' => $totalLoaded,
            'total_weighed' => $totalWeighed,
            'total_difference' => $totalDifference,
            'normal_count' => $comparisonData->where('status', 'normal')->count(),
            'overload_count' => $comparisonData->where('status', 'overload')->count(),
            'underload_count' => $comparisonData->where('status', 'underload')->count(),
            'has_issues' => $comparisonData->contains('status', '!=', 'normal'),
        ];
    }

    public function canCompare(Rake $rake): bool
    {
        return $this->hasRequiredData($rake);
    }

    private function hasRequiredData(Rake $rake): bool
    {
        $hasLoadings = $rake->wagonLoadings()->exists();
        $hasWeighment = $rake->rakeWeighments()->where('status', 'success')->exists();

        return $hasLoadings && $hasWeighment;
    }
}

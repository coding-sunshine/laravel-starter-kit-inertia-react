<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Penalty;
use App\Models\Rake;
use App\Models\RrDocument;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ReconcileRrData - 5-point validation reconciliation of RR documents against rake data
 *
 * Validates:
 * 1. Coal Quantity Match - RR weight vs rake weight (±2% tolerance)
 * 2. Wagon Count Match - RR wagon count vs rake wagon count (exact)
 * 3. Weight Validation - Individual wagon weights (max 50 MT per wagon)
 * 4. Timing Validation - RR receipt date vs rake delivery date (±3 days)
 * 5. Penalty Cross-Check - Demurrage charges match calculated values (±₹1000)
 */
final readonly class ReconcileRrData
{
    public function __construct(
        private CalculateDemurrageCharges $demurrageCalculator
    ) {}

    /**
     * Run reconciliation against RR document
     */
    public function reconcile(RrDocument $rrDocument, int $userId): array
    {
        return DB::transaction(function () use ($rrDocument, $userId): array {
            $rake = $rrDocument->rake;
            $rrData = json_decode($rrDocument->rr_details, true) ?? [];

            // Run all 5 reconciliation points
            $point1 = $this->validateCoalQuantity($rake, $rrData, $rrDocument);
            $point2 = $this->validateWagonCount($rake, $rrData);
            $point3 = $this->validateWeightPerWagon($rake, $rrData);
            $point4 = $this->validateTiming($rake, $rrDocument);
            $point5 = $this->validatePenalties($rake, $rrData);

            // Determine overall status
            $hasDiscrepancies = ! $point1['passed'] || ! $point2['passed']
                || ! $point3['passed'] || ! $point4['passed'] || ! $point5['passed'];

            // Update RR document
            $rrDocument->update([
                'document_status' => $hasDiscrepancies ? 'discrepancy' : 'verified',
                'has_discrepancy' => $hasDiscrepancies,
                'discrepancy_details' => $hasDiscrepancies ? json_encode([
                    'point_1' => $point1,
                    'point_2' => $point2,
                    'point_3' => $point3,
                    'point_4' => $point4,
                    'point_5' => $point5,
                ]) : null,
                'updated_by' => $userId,
            ]);

            return [
                'rr_document_id' => $rrDocument->id,
                'rake_id' => $rake->id,
                'status' => $hasDiscrepancies ? 'discrepancy' : 'verified',
                'has_discrepancies' => $hasDiscrepancies,
                'reconciliation_points' => [
                    'point_1_coal_quantity' => $point1,
                    'point_2_wagon_count' => $point2,
                    'point_3_weight_per_wagon' => $point3,
                    'point_4_timing' => $point4,
                    'point_5_penalties' => $point5,
                ],
                'reconciliation_timestamp' => now(),
            ];
        });
    }

    /**
     * Get reconciliation summary for a siding
     */
    public function getReconciliationSummary(int $sidingId): array
    {
        $documents = RrDocument::whereHas('rake', function ($query) use ($sidingId) {
            $query->where('siding_id', $sidingId);
        })->get();

        $total = $documents->count();
        $verified = $documents->where('document_status', 'verified')->count();
        $discrepancies = $documents->where('has_discrepancy', true)->count();
        $pending = $documents->where('document_status', 'received')->count();

        return [
            'siding_id' => $sidingId,
            'total_documents' => $total,
            'verified_documents' => $verified,
            'documents_with_discrepancies' => $discrepancies,
            'pending_reconciliation' => $pending,
            'verification_rate' => $total > 0 ? round(($verified / $total) * 100, 2) : 0,
            'discrepancy_rate' => $total > 0 ? round(($discrepancies / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Get all rakes pending RR verification
     */
    public function getPendingRrVerificationRakes(int $sidingId): Collection
    {
        return Rake::where('siding_id', $sidingId)
            ->whereIn('state', ['in_transit', 'delivered'])
            ->whereDoesntHave('rrDocument', function ($query) {
                $query->where('document_status', 'verified');
            })
            ->with('rrDocument')
            ->get();
    }

    /**
     * Get discrepancy details by type
     */
    public function getDiscrepanciesByType(int $sidingId): array
    {
        $documents = RrDocument::whereHas('rake', function ($query) use ($sidingId) {
            $query->where('siding_id', $sidingId);
        })
            ->where('has_discrepancy', true)
            ->get();

        $discrepanciesByType = [];

        foreach ($documents as $doc) {
            $details = json_decode($doc->discrepancy_details, true) ?? [];

            foreach ($details as $pointKey => $point) {
                if (! $point['passed']) {
                    $pointName = $point['name'] ?? $pointKey;
                    if (! isset($discrepanciesByType[$pointName])) {
                        $discrepanciesByType[$pointName] = [];
                    }
                    $discrepanciesByType[$pointName][] = [
                        'rr_document_id' => $doc->id,
                        'rake_id' => $doc->rake_id,
                        'details' => $point,
                    ];
                }
            }
        }

        return $discrepanciesByType;
    }

    /**
     * Point 1: Coal Quantity Match (±2% tolerance)
     */
    private function validateCoalQuantity(Rake $rake, array $rrData, RrDocument $rrDocument): array
    {
        $rakeWeight = $rake->loaded_weight_mt ?? 0;
        $rrWeight = $rrData['rr_weight_mt'] ?? $rrDocument->rr_weight_mt ?? 0;

        if (! $rrWeight) {
            return [
                'passed' => false,
                'name' => 'Coal Quantity Match',
                'rake_weight_mt' => $rakeWeight,
                'rr_weight_mt' => $rrWeight,
                'tolerance_percent' => 2,
                'variance_percent' => 0,
                'issue' => 'RR weight not found in document',
            ];
        }

        $variance = abs($rakeWeight - $rrWeight) / max($rakeWeight, $rrWeight) * 100;
        $passed = $variance <= 2.0;

        return [
            'passed' => $passed,
            'name' => 'Coal Quantity Match',
            'rake_weight_mt' => $rakeWeight,
            'rr_weight_mt' => $rrWeight,
            'tolerance_percent' => 2,
            'variance_percent' => round($variance, 2),
            'difference_mt' => round(abs($rakeWeight - $rrWeight), 2),
            'status' => $passed ? 'Match within tolerance' : 'Weight mismatch exceeds tolerance',
        ];
    }

    /**
     * Point 2: Wagon Count Match (exact, zero tolerance)
     */
    private function validateWagonCount(Rake $rake, array $rrData): array
    {
        $rakeWagonCount = $rake->wagon_count ?? 0;
        $rrWagonCount = $rrData['wagon_count'] ?? 0;

        $passed = $rakeWagonCount === $rrWagonCount && $rrWagonCount > 0;

        return [
            'passed' => $passed,
            'name' => 'Wagon Count Match',
            'rake_wagon_count' => $rakeWagonCount,
            'rr_wagon_count' => $rrWagonCount,
            'tolerance' => 0,
            'difference' => $rakeWagonCount - $rrWagonCount,
            'status' => $passed ? 'Wagon count matches exactly' : 'Wagon count mismatch',
        ];
    }

    /**
     * Point 3: Weight per Wagon Validation (max 50 MT per wagon)
     */
    private function validateWeightPerWagon(Rake $rake, array $rrData): array
    {
        $totalWeight = $rrData['rr_weight_mt'] ?? 0;
        $wagonCount = $rrData['wagon_count'] ?? $rake->wagon_count ?? 1;
        $maxWeightPerWagon = 50; // MT

        if (! $wagonCount || $wagonCount === 0) {
            return [
                'passed' => false,
                'name' => 'Weight per Wagon Validation',
                'total_weight_mt' => $totalWeight,
                'wagon_count' => $wagonCount,
                'max_per_wagon_mt' => $maxWeightPerWagon,
                'avg_weight_per_wagon' => 0,
                'status' => 'Cannot calculate: wagon count is zero',
            ];
        }

        $avgWeightPerWagon = $totalWeight / $wagonCount;
        $passed = $avgWeightPerWagon <= $maxWeightPerWagon && $totalWeight > 0;

        return [
            'passed' => $passed,
            'name' => 'Weight per Wagon Validation',
            'total_weight_mt' => $totalWeight,
            'wagon_count' => $wagonCount,
            'max_per_wagon_mt' => $maxWeightPerWagon,
            'avg_weight_per_wagon' => round($avgWeightPerWagon, 2),
            'status' => $passed ? 'Weight per wagon within limits' : 'Average weight exceeds wagon capacity',
        ];
    }

    /**
     * Point 4: Timing Validation (RR received within ±3 days of rake delivery)
     */
    private function validateTiming(Rake $rake, RrDocument $rrDocument): array
    {
        $rakeDeliveryDate = $rake->rr_actual_date ?? $rake->rr_expected_date;
        $rrReceivedDate = $rrDocument->rr_received_date;
        $toleranceDays = 3;

        if (! $rakeDeliveryDate || ! $rrReceivedDate) {
            return [
                'passed' => false,
                'name' => 'Timing Validation',
                'rake_delivery_date' => $rakeDeliveryDate?->toDateString(),
                'rr_received_date' => $rrReceivedDate?->toDateString(),
                'tolerance_days' => $toleranceDays,
                'days_difference' => null,
                'status' => 'Cannot validate: missing delivery or receipt date',
            ];
        }

        $daysDifference = $rakeDeliveryDate->diffInDays($rrReceivedDate);
        $passed = $daysDifference <= $toleranceDays;

        return [
            'passed' => $passed,
            'name' => 'Timing Validation',
            'rake_delivery_date' => $rakeDeliveryDate->toDateString(),
            'rr_received_date' => $rrReceivedDate->toDateString(),
            'tolerance_days' => $toleranceDays,
            'days_difference' => $daysDifference,
            'status' => $passed
                ? "RR received within {$toleranceDays} days of delivery"
                : "RR received {$daysDifference} days after delivery (exceeds tolerance)",
        ];
    }

    /**
     * Point 5: Penalty Cross-Check (±₹1000 tolerance)
     */
    private function validatePenalties(Rake $rake, array $rrData): array
    {
        // Calculate expected demurrage
        $demurrageCalc = $this->demurrageCalculator->calculateForRake($rake);
        $expectedDemurrage = $demurrageCalc['demurrage_charge'] ?? 0;

        // Get actual penalties from database
        $actualPenalties = Penalty::where('rake_id', $rake->id)
            ->where('penalty_status', '!=', 'waived')
            ->sum('penalty_amount');

        $penaltyTolerance = 1000; // ₹1000
        $difference = abs($expectedDemurrage - $actualPenalties);
        $passed = $difference <= $penaltyTolerance;

        return [
            'passed' => $passed,
            'name' => 'Penalty Cross-Check',
            'expected_demurrage' => round($expectedDemurrage, 2),
            'actual_penalties' => round($actualPenalties, 2),
            'tolerance' => $penaltyTolerance,
            'difference' => round($difference, 2),
            'status' => $passed
                ? 'Penalties reconcile within tolerance'
                : "Penalty discrepancy of ₹{$difference}",
        ];
    }
}

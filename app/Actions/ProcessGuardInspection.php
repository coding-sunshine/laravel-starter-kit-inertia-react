<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\GuardInspection;
use App\Models\Rake;
use App\Models\Weighment;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * ProcessGuardInspection - Handle guard inspection and overload detection
 *
 * Guards inspect rakes before departure, verifying:
 * - Physical condition of wagons
 * - Weight compliance (overload prevention)
 * - Documentation completeness
 */
final readonly class ProcessGuardInspection
{
    public function __construct() {}

    /**
     * Record guard inspection and check for overloads
     *
     * @param array{
     *     rake_id: int,
     *     is_approved: bool,
     *     remarks?: string,
     *     actual_weight_mt?: float,
     *     check_overload?: bool,
     * } $data
     */
    public function inspect(array $data, int $userId): GuardInspection
    {
        return DB::transaction(function () use ($data, $userId): GuardInspection {
            $rake = Rake::findOrFail($data['rake_id']);

            // Validate rake state
            if ($rake->state !== 'staged') {
                throw new InvalidArgumentException('Can only inspect staged rakes');
            }

            // Create inspection record
            $inspection = GuardInspection::create([
                'rake_id' => $rake->id,
                'inspection_time' => now(),
                'is_approved' => $data['is_approved'],
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
            ]);

            // If actual weight provided, create weighment record
            if (isset($data['actual_weight_mt'])) {
                $this->recordWholement($rake, $data['actual_weight_mt'], $userId);
            }

            // Check for overload if requested
            if ($data['check_overload'] ?? false) {
                $this->checkOverload($rake, $data['actual_weight_mt'] ?? $rake->loaded_weight_mt ?? 0);
            }

            // Update rake state based on inspection result
            if ($data['is_approved']) {
                $rake->update([
                    'state' => 'approved',
                    'updated_by' => $userId,
                ]);
            } else {
                $rake->update([
                    'state' => 'rejected',
                    'updated_by' => $userId,
                ]);
            }

            return $inspection->refresh();
        });
    }

    /**
     * Check for overload condition
     *
     * @throws Exception If overloaded
     */
    public function checkOverload(Rake $rake, float $actualWeight): array
    {
        // Get permitted capacity from rake configuration
        // Standard coal rake: 60 wagons × 50 MT = 3000 MT max
        $maxCapacity = ($rake->wagon_count ?? 60) * 50;

        $isOverloaded = $actualWeight > $maxCapacity;
        $overloadMargin = max(0, $actualWeight - $maxCapacity);

        if ($isOverloaded) {
            throw new InvalidArgumentException(
                "Rake overloaded by {$overloadMargin} MT (Max: {$maxCapacity} MT, Actual: {$actualWeight} MT)"
            );
        }

        return [
            'max_capacity_mt' => $maxCapacity,
            'actual_weight_mt' => $actualWeight,
            'is_overloaded' => $isOverloaded,
            'margin_mt' => $maxCapacity - $actualWeight,
            'margin_percent' => round((($maxCapacity - $actualWeight) / $maxCapacity) * 100, 2),
        ];
    }

    /**
     * Get inspection status for a rake
     */
    public function getInspectionStatus(Rake $rake): array
    {
        $inspection = $rake->guardInspection;
        $weighments = $rake->weighments()->get();

        return [
            'has_inspection' => $inspection !== null,
            'is_approved' => $inspection?->is_approved ?? false,
            'inspection_time' => $inspection?->inspection_time,
            'inspection_remarks' => $inspection?->remarks,
            'inspection_by' => $inspection?->createdBy->name,
            'weighment_count' => $weighments->count(),
            'last_weighment' => $weighments->last()?->weighment_time,
            'net_weight_mt' => $weighments->last()?->net_weight_mt ?? $rake->loaded_weight_mt ?? 0,
        ];
    }

    /**
     * Approve rake for departure
     */
    public function approveDeparture(Rake $rake, int $userId): Rake
    {
        return DB::transaction(function () use ($rake, $userId): Rake {
            $inspection = $rake->guardInspection;

            if (! $inspection || ! $inspection->is_approved) {
                throw new InvalidArgumentException('Rake must be inspected and approved by guard');
            }

            $rake->update([
                'state' => 'approved',
                'updated_by' => $userId,
            ]);

            return $rake->refresh();
        });
    }

    /**
     * Reject rake (returns to loading for adjustments)
     */
    public function rejectRake(Rake $rake, string $reason, int $userId): Rake
    {
        return DB::transaction(function () use ($rake, $reason, $userId): Rake {
            $rake->update([
                'state' => 'loading',
                'updated_by' => $userId,
            ]);

            // Record rejection in a new inspection attempt
            GuardInspection::create([
                'rake_id' => $rake->id,
                'inspection_time' => now(),
                'is_approved' => false,
                'remarks' => "Rejected: {$reason}",
                'created_by' => $userId,
            ]);

            return $rake->refresh();
        });
    }

    /**
     * Record in-motion weighment
     */
    private function recordWholement(Rake $rake, float $actualWeight, int $userId): Weighment
    {
        $weighment = Weighment::create([
            'rake_id' => $rake->id,
            'gross_weight_mt' => $actualWeight,
            'tare_weight_mt' => 0, // Will be subtracted later
            'net_weight_mt' => $actualWeight,
            'weighment_time' => now(),
            'weighment_type' => 'in_motion',
            'created_by' => $userId,
        ]);

        // Update rake with actual weight
        $rake->update([
            'loaded_weight_mt' => $actualWeight,
        ]);

        return $weighment;
    }
}

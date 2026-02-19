<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Rake;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * CreateRake - Initialize and manage rake loading operations
 *
 * Rakes represent trains at a siding awaiting coal loading.
 * Workflow: pending → loading → staged → departed → in_transit → delivered
 */
final readonly class CreateRake
{
    public function __construct() {}

    /**
     * Create a new rake
     *
     * @param array{
     *     siding_id: int,
     *     rake_number: string,
     *     rake_type?: string,
     *     wagon_count: int,
     *     free_time_minutes?: int,
     *     rr_expected_date?: string,
     * } $data
     */
    public function handle(array $data, int $userId): Rake
    {
        return DB::transaction(function () use ($data, $userId): Rake {
            // Generate rake number if not provided
            $rakeNumber = $data['rake_number'] ?? $this->generateRakeNumber($data['siding_id']);

            // Create rake record
            $rake = Rake::create([
                'siding_id' => $data['siding_id'],
                'rake_number' => $rakeNumber,
                'rake_type' => $data['rake_type'] ?? 'Coal',
                'wagon_count' => $data['wagon_count'],
                'state' => 'pending',
                'loading_start_time' => null,
                'loading_end_time' => null,
                'free_time_minutes' => $data['free_time_minutes'] ?? 144, // 6 days default
                'rr_expected_date' => $data['rr_expected_date'] ?? null,
                'created_by' => $userId,
            ]);

            return $rake->refresh();
        });
    }

    /**
     * Start loading a rake
     */
    public function startLoading(Rake $rake, int $userId): Rake
    {
        if ($rake->state !== 'pending') {
            throw new InvalidArgumentException('Can only start loading rakes in pending state');
        }

        return DB::transaction(function () use ($rake, $userId): Rake {
            $rake->update([
                'state' => 'loading',
                'loading_start_time' => now(),
                'updated_by' => $userId,
            ]);

            return $rake->refresh();
        });
    }

    /**
     * Complete loading and stage the rake for departure
     */
    public function stageRake(Rake $rake, float $loadedWeight, int $userId): Rake
    {
        if (! in_array($rake->state, ['loading', 'pending'])) {
            throw new InvalidArgumentException('Can only stage rakes in loading or pending state');
        }

        return DB::transaction(function () use ($rake, $loadedWeight, $userId): Rake {
            $rake->update([
                'state' => 'staged',
                'loaded_weight_mt' => $loadedWeight,
                'loading_end_time' => now(),
                'updated_by' => $userId,
            ]);

            return $rake->refresh();
        });
    }

    /**
     * Mark rake as departed
     */
    public function depart(Rake $rake, int $userId): Rake
    {
        if ($rake->state !== 'staged') {
            throw new InvalidArgumentException('Can only depart rakes in staged state');
        }

        return DB::transaction(function () use ($rake, $userId): Rake {
            $rake->update([
                'state' => 'departed',
                'updated_by' => $userId,
            ]);

            return $rake->refresh();
        });
    }

    /**
     * Mark rake as in transit
     */
    public function markInTransit(Rake $rake, int $userId): Rake
    {
        if (! in_array($rake->state, ['departed', 'staged'])) {
            throw new InvalidArgumentException('Can only mark staged/departed rakes as in transit');
        }

        return DB::transaction(function () use ($rake, $userId): Rake {
            $rake->update([
                'state' => 'in_transit',
                'updated_by' => $userId,
            ]);

            return $rake->refresh();
        });
    }

    /**
     * Mark rake as delivered at destination
     */
    public function markDelivered(Rake $rake, int $userId): Rake
    {
        if ($rake->state !== 'in_transit') {
            throw new InvalidArgumentException('Can only mark in_transit rakes as delivered');
        }

        return DB::transaction(function () use ($rake, $userId): Rake {
            $rake->update([
                'state' => 'delivered',
                'rr_actual_date' => now(),
                'updated_by' => $userId,
            ]);

            return $rake->refresh();
        });
    }

    /**
     * Assign a wagon to a rake
     */
    public function assignWagon(Rake $rake, int $wagonId, int $position, int $userId): void
    {
        if ($rake->state !== 'loading' && $rake->state !== 'pending') {
            throw new InvalidArgumentException('Can only assign wagons to pending/loading rakes');
        }

        $rake->wagons()->updateOrCreate(
            ['wagon_id' => $wagonId],
            ['position' => $position]
        );
    }

    /**
     * Get assigned wagons for a rake (in order)
     */
    public function getAssignedWagons(Rake $rake): Collection
    {
        return $rake->wagons()
            ->orderBy('position', 'asc')
            ->with('wagon')
            ->get();
    }

    /**
     * Get unassigned wagons for a siding
     */
    public function getAvailableWagons(int $sidingId): Collection
    {
        // Get wagons not currently assigned to any rake
        return Rake::where('siding_id', $sidingId)
            ->whereNotIn('state', ['departed', 'in_transit', 'delivered'])
            ->with('wagons')
            ->get()
            ->pluck('wagons')
            ->flatten()
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get loading progress for a rake
     */
    public function getLoadingProgress(Rake $rake): array
    {
        $wagons = $rake->wagons()->count();
        $loadedWagons = $rake->wagons()->where('status', 'loaded')->count();

        return [
            'total_wagons' => $rake->wagon_count,
            'assigned_wagons' => $wagons,
            'loaded_wagons' => $loadedWagons,
            'pending_wagons' => $wagons - $loadedWagons,
            'progress_percent' => round(($loadedWagons / max($wagons, 1)) * 100, 2),
            'loaded_weight_mt' => $rake->loaded_weight_mt ?? 0,
            'predicted_weight_mt' => $rake->predicted_weight_mt,
        ];
    }

    /**
     * Calculate demurrage for a rake
     */
    public function calculateDemurrage(Rake $rake): array
    {
        if (! $rake->loading_end_time || ! $rake->rr_expected_date) {
            return [
                'free_hours' => $rake->free_time_minutes / 60,
                'demurrage_hours' => 0,
                'demurrage_penalty' => 0,
            ];
        }

        // Calculate actual dwell time
        $dwellHours = $rake->loading_end_time->diffInHours(
            $rake->rr_expected_date ?: now()
        );

        $freeHours = $rake->free_time_minutes / 60;
        $demurrageHours = max(0, $dwellHours - $freeHours);

        // Standard demurrage penalty: ₹50 per MT per hour
        $demurragePenalty = $demurrageHours * ($rake->loaded_weight_mt ?? 0) * 50;

        return [
            'free_hours' => $freeHours,
            'dwell_hours' => $dwellHours,
            'demurrage_hours' => $demurrageHours,
            'demurrage_penalty' => $demurragePenalty,
        ];
    }

    /**
     * Generate unique rake number
     */
    private function generateRakeNumber(int $sidingId): string
    {
        $siding = \App\Models\Siding::find($sidingId);
        $sidingCode = $siding?->code ?? 'UNK';

        // Format: RAKE-CODE-NNNN (e.g., RAKE-PKR-0001)
        $count = Rake::where('siding_id', $sidingId)->count() + 1;

        return sprintf('RAKE-%s-%04d', $sidingCode, $count);
    }
}

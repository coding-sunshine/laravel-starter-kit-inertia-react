<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\VehicleArrival;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CreateVehicleArrival - Register a truck arrival and initiate unloading
 *
 * Handles the complete workflow for registering a vehicle arrival at a siding,
 * including weight measurement and unloading initiation.
 */
final readonly class CreateVehicleArrival
{
    public function __construct() {}

    /**
     * Create a new vehicle arrival
     *
     * @param array{
     *     siding_id: int,
     *     vehicle_id: int,
     *     indent_id?: int|null,
     *     gross_weight?: float|null,
     *     tare_weight?: float|null,
     *     arrived_at?: string,
     *     notes?: string|null,
     * } $data
     */
    public function handle(array $data, int $userId): VehicleArrival
    {
        return DB::transaction(function () use ($data, $userId): VehicleArrival {
            // Calculate net weight if both gross and tare are provided
            $netWeight = null;
            if (isset($data['gross_weight']) && isset($data['tare_weight'])) {
                $netWeight = $data['gross_weight'] - $data['tare_weight'];
            }

            // Create the vehicle arrival record
            $arrival = VehicleArrival::create([
                'siding_id' => $data['siding_id'],
                'vehicle_id' => $data['vehicle_id'],
                'indent_id' => $data['indent_id'] ?? null,
                'status' => 'pending',
                'arrived_at' => $data['arrived_at'] ?? now(),
                'shift' => $data['shift'] ?? null,
                'gross_weight' => $data['gross_weight'] ?? null,
                'tare_weight' => $data['tare_weight'] ?? null,
                'net_weight' => $netWeight,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            // If indent is associated, update indent status to reflect this vehicle arrival
            if ($arrival->indent_id) {
                $arrival->indent->update(['status' => 'in_transit']);
            }

            return $arrival->refresh();
        });
    }

    /**
     * Get pending arrivals for a siding
     */
    public function getPendingArrivals(int $sidingId): Collection
    {
        return VehicleArrival::where('siding_id', $sidingId)
            ->where('status', 'pending')
            ->orderBy('arrived_at', 'desc')
            ->with('vehicle', 'indent')
            ->get();
    }

    /**
     * Get currently unloading vehicles at a siding
     */
    public function getUnloadingVehicles(int $sidingId): Collection
    {
        return VehicleArrival::where('siding_id', $sidingId)
            ->where('status', 'unloading')
            ->orderBy('unloading_started_at', 'asc')
            ->with('vehicle', 'indent')
            ->get();
    }

    /**
     * Get unloading summary for a siding
     */
    public function getUnloadingSummary(int $sidingId): array
    {
        $arrivals = VehicleArrival::where('siding_id', $sidingId)
            ->whereIn('status', ['pending', 'unloading', 'unloaded'])
            ->with('vehicle')
            ->get();

        return [
            'total_pending' => $arrivals->where('status', 'pending')->count(),
            'total_unloading' => $arrivals->where('status', 'unloading')->count(),
            'total_unloaded' => $arrivals->where('status', 'unloaded')->count(),
            'total_coal_pending' => $arrivals->where('status', 'pending')->sum('gross_weight'),
            'total_coal_unloaded' => $arrivals->where('status', 'unloaded')->sum('unloaded_quantity'),
        ];
    }
}

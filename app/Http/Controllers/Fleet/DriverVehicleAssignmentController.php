<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreDriverVehicleAssignmentRequest;
use App\Http\Requests\Fleet\UpdateDriverVehicleAssignmentRequest;
use App\Models\Fleet\DriverVehicleAssignment;
use App\Models\Fleet\Vehicle;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DriverVehicleAssignmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DriverVehicleAssignment::class);

        $assignments = DriverVehicleAssignment::query()
            ->with(['driver', 'vehicle', 'assignedByUser'])
            ->when($request->boolean('is_current'), fn ($q) => $q->where('is_current', true))
            ->orderByDesc('assigned_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/DriverVehicleAssignments/Index', [
            'assignments' => $assignments,
            'filters' => $request->only(['is_current']),
        ]);
    }

    public function store(StoreDriverVehicleAssignmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $orgId = TenantContext::id();

        // End any current assignment for this vehicle and for this driver
        DriverVehicleAssignment::query()
            ->where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('vehicle_id', $validated['vehicle_id'])
                ->orWhere('driver_id', $validated['driver_id']))
            ->where('is_current', true)
            ->update(['is_current' => false, 'unassigned_date' => $validated['assigned_date']]);

        DriverVehicleAssignment::create([
            'organization_id' => $orgId,
            'driver_id' => $validated['driver_id'],
            'vehicle_id' => $validated['vehicle_id'],
            'assignment_type' => $validated['assignment_type'],
            'assigned_date' => $validated['assigned_date'],
            'is_current' => true,
            'notes' => $validated['notes'] ?? null,
            'assigned_by' => $request->user()->id,
        ]);

        // Keep vehicles.current_driver_id in sync
        Vehicle::withoutGlobalScopes()->where('id', $validated['vehicle_id'])->update([
            'current_driver_id' => $validated['driver_id'],
        ]);

        return back()->with('flash', ['status' => 'success', 'message' => 'Driver assigned.']);
    }

    public function update(UpdateDriverVehicleAssignmentRequest $request, DriverVehicleAssignment $driverVehicleAssignment): RedirectResponse
    {
        $validated = $request->validated();
        $driverVehicleAssignment->update($validated);

        if (isset($validated['unassigned_date']) && $validated['unassigned_date']) {
            $driverVehicleAssignment->update(['is_current' => false]);
            Vehicle::withoutGlobalScopes()->where('id', $driverVehicleAssignment->vehicle_id)->update([
                'current_driver_id' => null,
            ]);
        }

        return back()->with('flash', ['status' => 'success', 'message' => 'Assignment updated.']);
    }

    public function destroy(DriverVehicleAssignment $driverVehicleAssignment): RedirectResponse
    {
        $this->authorize('delete', $driverVehicleAssignment);
        $vehicleId = $driverVehicleAssignment->vehicle_id;
        $driverVehicleAssignment->delete();

        if ($driverVehicleAssignment->is_current) {
            Vehicle::withoutGlobalScopes()->where('id', $vehicleId)->update(['current_driver_id' => null]);
        }

        return back()->with('flash', ['status' => 'success', 'message' => 'Assignment removed.']);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreVehicleRequest;
use App\Http\Requests\Fleet\UpdateVehicleRequest;
use App\Models\Fleet\DriverVehicleAssignment;
use App\Models\Fleet\Vehicle;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class VehicleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Vehicle::class);
        $vehicles = Vehicle::query()
            ->with(['homeLocation', 'currentDriver'])
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('registration')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/Vehicles/Index', [
            'vehicles' => $vehicles,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Vehicle::class);
        $enum = fn ($cases) => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], $cases);
        return Inertia::render('Fleet/Vehicles/Create', [
            'fuelTypes' => $enum(\App\Enums\Fleet\VehicleFuelType::cases()),
            'vehicleTypes' => $enum(\App\Enums\Fleet\VehicleType::cases()),
            'statuses' => $enum(\App\Enums\Fleet\VehicleStatus::cases()),
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        Vehicle::create($request->validated());

        return to_route('fleet.vehicles.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle created.']);
    }

    public function show(Vehicle $vehicle): Response
    {
        $this->authorize('view', $vehicle);
        $vehicle->load(['homeLocation', 'currentDriver', 'driverAssignments' => fn ($q) => $q->with('driver')->orderByDesc('assigned_date')]);
        return Inertia::render('Fleet/Vehicles/Show', [
            'vehicle' => $vehicle,
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'assignmentTypes' => array_map(fn ($c) => ['name' => $c->name, 'value' => $c->value], \App\Enums\Fleet\AssignmentType::cases()),
        ]);
    }

    public function assignDriver(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);
        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'assignment_type' => ['required', 'string', 'in:primary,secondary,temporary'],
            'assigned_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:65535'],
        ]);
        $orgId = TenantContext::id();

        DriverVehicleAssignment::query()
            ->where('organization_id', $orgId)
            ->where('is_current', true)
            ->where(function ($q) use ($vehicle, $validated) {
                $q->where('vehicle_id', $vehicle->id)->orWhere('driver_id', $validated['driver_id']);
            })
            ->update(['is_current' => false, 'unassigned_date' => $validated['assigned_date']]);

        DriverVehicleAssignment::create([
            'organization_id' => $orgId,
            'driver_id' => $validated['driver_id'],
            'vehicle_id' => $vehicle->id,
            'assignment_type' => $validated['assignment_type'],
            'assigned_date' => $validated['assigned_date'],
            'is_current' => true,
            'notes' => $validated['notes'] ?? null,
            'assigned_by' => $request->user()->id,
        ]);

        $vehicle->update(['current_driver_id' => $validated['driver_id']]);

        return redirect()->route('fleet.vehicles.show', $vehicle)->with('flash', ['status' => 'success', 'message' => 'Driver assigned.']);
    }

    public function unassignDriver(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);
        $assignment = DriverVehicleAssignment::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('is_current', true)
            ->first();
        if ($assignment) {
            $assignment->update(['is_current' => false, 'unassigned_date' => now()->toDateString()]);
        }
        $vehicle->update(['current_driver_id' => null]);
        return redirect()->route('fleet.vehicles.show', $vehicle)->with('flash', ['status' => 'success', 'message' => 'Driver unassigned.']);
    }

    public function edit(Vehicle $vehicle): Response
    {
        $this->authorize('update', $vehicle);
        $enum = fn ($cases) => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], $cases);
        return Inertia::render('Fleet/Vehicles/Edit', [
            'vehicle' => $vehicle,
            'fuelTypes' => $enum(\App\Enums\Fleet\VehicleFuelType::cases()),
            'vehicleTypes' => $enum(\App\Enums\Fleet\VehicleType::cases()),
            'statuses' => $enum(\App\Enums\Fleet\VehicleStatus::cases()),
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);
        $vehicle->update($request->validated());
        return to_route('fleet.vehicles.show', $vehicle)->with('flash', ['status' => 'success', 'message' => 'Vehicle updated.']);
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('delete', $vehicle);
        $vehicle->delete();
        return to_route('fleet.vehicles.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle deleted.']);
    }
}

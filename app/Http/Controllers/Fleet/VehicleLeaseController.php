<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreVehicleLeaseRequest;
use App\Http\Requests\Fleet\UpdateVehicleLeaseRequest;
use App\Models\Fleet\VehicleLease;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class VehicleLeaseController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', VehicleLease::class);
        $leases = VehicleLease::query()
            ->with(['vehicle'])
            ->when($request->input('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('start_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/VehicleLeases/Index', [
            'vehicleLeases' => $leases,
            'filters' => $request->only(['vehicle_id', 'status']),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\VehicleLeaseStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', VehicleLease::class);

        return Inertia::render('Fleet/VehicleLeases/Create', [
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\VehicleLeaseStatus::cases()),
        ]);
    }

    public function store(StoreVehicleLeaseRequest $request): RedirectResponse
    {
        $this->authorize('create', VehicleLease::class);
        VehicleLease::create($request->validated());

        return to_route('fleet.vehicle-leases.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle lease created.']);
    }

    public function show(VehicleLease $vehicle_lease): Response
    {
        $this->authorize('view', $vehicle_lease);
        $vehicle_lease->load(['vehicle']);

        return Inertia::render('Fleet/VehicleLeases/Show', ['vehicleLease' => $vehicle_lease]);
    }

    public function edit(VehicleLease $vehicle_lease): Response
    {
        $this->authorize('update', $vehicle_lease);

        return Inertia::render('Fleet/VehicleLeases/Edit', [
            'vehicleLease' => $vehicle_lease,
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\VehicleLeaseStatus::cases()),
        ]);
    }

    public function update(UpdateVehicleLeaseRequest $request, VehicleLease $vehicle_lease): RedirectResponse
    {
        $this->authorize('update', $vehicle_lease);
        $vehicle_lease->update($request->validated());

        return to_route('fleet.vehicle-leases.show', $vehicle_lease)->with('flash', ['status' => 'success', 'message' => 'Vehicle lease updated.']);
    }

    public function destroy(VehicleLease $vehicle_lease): RedirectResponse
    {
        $this->authorize('delete', $vehicle_lease);
        $vehicle_lease->delete();

        return to_route('fleet.vehicle-leases.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle lease deleted.']);
    }
}

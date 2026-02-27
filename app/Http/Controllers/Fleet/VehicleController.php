<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Vehicle;
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
        return Inertia::render('Fleet/Vehicles/Create', [
            'fuelTypes' => \App\Enums\Fleet\VehicleFuelType::cases(),
            'vehicleTypes' => \App\Enums\Fleet\VehicleType::cases(),
            'statuses' => \App\Enums\Fleet\VehicleStatus::cases(),
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Vehicle::class);
        $validated = $request->validate([
            'registration' => ['required', 'string', 'max:50'],
            'vin' => ['nullable', 'string', 'size:17'],
            'fleet_number' => ['nullable', 'string', 'max:50'],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'fuel_type' => ['required', 'string', 'in:petrol,diesel,electric,hybrid'],
            'vehicle_type' => ['required', 'string', 'in:car,van,truck,bus,motorcycle'],
            'home_location_id' => ['nullable', 'exists:locations,id'],
            'current_driver_id' => ['nullable', 'exists:drivers,id'],
            'status' => ['required', 'string', 'in:active,maintenance,vor,disposed'],
            'compliance_status' => ['nullable', 'string', 'in:compliant,expiring_soon,expired'],
        ]);
        Vehicle::create($validated);
        return to_route('fleet.vehicles.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle created.']);
    }

    public function show(Vehicle $vehicle): Response
    {
        $this->authorize('view', $vehicle);
        $vehicle->load(['homeLocation', 'currentDriver']);
        return Inertia::render('Fleet/Vehicles/Show', ['vehicle' => $vehicle]);
    }

    public function edit(Vehicle $vehicle): Response
    {
        $this->authorize('update', $vehicle);
        return Inertia::render('Fleet/Vehicles/Edit', [
            'vehicle' => $vehicle,
            'fuelTypes' => \App\Enums\Fleet\VehicleFuelType::cases(),
            'vehicleTypes' => \App\Enums\Fleet\VehicleType::cases(),
            'statuses' => \App\Enums\Fleet\VehicleStatus::cases(),
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);
        $validated = $request->validate([
            'registration' => ['required', 'string', 'max:50'],
            'vin' => ['nullable', 'string', 'size:17'],
            'fleet_number' => ['nullable', 'string', 'max:50'],
            'make' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'fuel_type' => ['required', 'string', 'in:petrol,diesel,electric,hybrid'],
            'vehicle_type' => ['required', 'string', 'in:car,van,truck,bus,motorcycle'],
            'home_location_id' => ['nullable', 'exists:locations,id'],
            'current_driver_id' => ['nullable', 'exists:drivers,id'],
            'status' => ['required', 'string', 'in:active,maintenance,vor,disposed'],
            'compliance_status' => ['nullable', 'string', 'in:compliant,expiring_soon,expired'],
        ]);
        $vehicle->update($validated);
        return to_route('fleet.vehicles.show', $vehicle)->with('flash', ['status' => 'success', 'message' => 'Vehicle updated.']);
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('delete', $vehicle);
        $vehicle->delete();
        return to_route('fleet.vehicles.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle deleted.']);
    }
}

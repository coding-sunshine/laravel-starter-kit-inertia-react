<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\VehicleDiscStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreVehicleDiscRequest;
use App\Http\Requests\Fleet\UpdateVehicleDiscRequest;
use App\Models\Fleet\OperatorLicence;
use App\Models\Fleet\Vehicle;
use App\Models\Fleet\VehicleDisc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class VehicleDiscController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', VehicleDisc::class);
        $discs = VehicleDisc::query()
            ->with(['vehicle', 'operatorLicence'])
            ->when($request->input('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('valid_to')
            ->paginate(15)
            ->withQueryString();

        $vehicles = Vehicle::query()->orderBy('registration')->get(['id', 'registration'])->map(fn ($v) => ['id' => $v->id, 'name' => $v->registration]);

        return Inertia::render('Fleet/VehicleDiscs/Index', [
            'vehicleDiscs' => $discs,
            'filters' => $request->only(['vehicle_id', 'status']),
            'vehicles' => $vehicles,
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], VehicleDiscStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', VehicleDisc::class);
        $vehicles = Vehicle::query()->orderBy('registration')->get(['id', 'registration'])->map(fn ($v) => ['id' => $v->id, 'name' => $v->registration]);
        $operatorLicences = OperatorLicence::query()->orderBy('license_number')->get(['id', 'license_number'])->map(fn ($o) => ['id' => $o->id, 'name' => $o->license_number]);

        return Inertia::render('Fleet/VehicleDiscs/Create', [
            'vehicles' => $vehicles,
            'operatorLicences' => $operatorLicences,
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], VehicleDiscStatus::cases()),
        ]);
    }

    public function store(StoreVehicleDiscRequest $request): RedirectResponse
    {
        $this->authorize('create', VehicleDisc::class);
        VehicleDisc::create($request->validated());
        return to_route('fleet.vehicle-discs.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle disc created.']);
    }

    public function show(VehicleDisc $vehicle_disc): Response
    {
        $this->authorize('view', $vehicle_disc);
        $vehicle_disc->load(['vehicle', 'operatorLicence']);
        return Inertia::render('Fleet/VehicleDiscs/Show', ['vehicleDisc' => $vehicle_disc]);
    }

    public function edit(VehicleDisc $vehicle_disc): Response
    {
        $this->authorize('update', $vehicle_disc);
        $vehicles = Vehicle::query()->orderBy('registration')->get(['id', 'registration'])->map(fn ($v) => ['id' => $v->id, 'name' => $v->registration]);
        $operatorLicences = OperatorLicence::query()->orderBy('license_number')->get(['id', 'license_number'])->map(fn ($o) => ['id' => $o->id, 'name' => $o->license_number]);

        return Inertia::render('Fleet/VehicleDiscs/Edit', [
            'vehicleDisc' => $vehicle_disc,
            'vehicles' => $vehicles,
            'operatorLicences' => $operatorLicences,
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], VehicleDiscStatus::cases()),
        ]);
    }

    public function update(UpdateVehicleDiscRequest $request, VehicleDisc $vehicle_disc): RedirectResponse
    {
        $this->authorize('update', $vehicle_disc);
        $vehicle_disc->update($request->validated());
        return to_route('fleet.vehicle-discs.show', $vehicle_disc)->with('flash', ['status' => 'success', 'message' => 'Vehicle disc updated.']);
    }

    public function destroy(VehicleDisc $vehicle_disc): RedirectResponse
    {
        $this->authorize('delete', $vehicle_disc);
        $vehicle_disc->delete();
        return to_route('fleet.vehicle-discs.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle disc deleted.']);
    }
}

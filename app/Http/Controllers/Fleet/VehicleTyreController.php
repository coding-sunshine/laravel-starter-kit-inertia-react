<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreVehicleTyreRequest;
use App\Http\Requests\Fleet\UpdateVehicleTyreRequest;
use App\Models\Fleet\TyreInventory;
use App\Models\Fleet\Vehicle;
use App\Models\Fleet\VehicleTyre;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class VehicleTyreController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', VehicleTyre::class);
        $orgId = TenantContext::id();
        $tyres = VehicleTyre::query()
            ->with(['vehicle', 'tyreInventory'])
            ->whereHas('vehicle', fn ($q) => $q->where('organization_id', $orgId))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/VehicleTyres/Index', [
            'vehicleTyres' => $tyres,
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'tyreInventory' => TyreInventory::query()->orderBy('size')->get(['id', 'size', 'brand'])->map(fn ($t): array => ['id' => $t->id, 'label' => mb_trim($t->size.' '.($t->brand ?? ''))]),
            'positionOptions' => [
                ['value' => 'front_left', 'name' => 'Front left'],
                ['value' => 'front_right', 'name' => 'Front right'],
                ['value' => 'rear_left', 'name' => 'Rear left'],
                ['value' => 'rear_right', 'name' => 'Rear right'],
                ['value' => 'spare', 'name' => 'Spare'],
                ['value' => 'other', 'name' => 'Other'],
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', VehicleTyre::class);

        return Inertia::render('Fleet/VehicleTyres/Create', [
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'tyreInventory' => TyreInventory::query()->orderBy('size')->get(['id', 'size', 'brand'])->map(fn ($t): array => ['id' => $t->id, 'label' => mb_trim($t->size.' '.($t->brand ?? ''))]),
            'positionOptions' => [
                ['value' => 'front_left', 'name' => 'Front left'],
                ['value' => 'front_right', 'name' => 'Front right'],
                ['value' => 'rear_left', 'name' => 'Rear left'],
                ['value' => 'rear_right', 'name' => 'Rear right'],
                ['value' => 'spare', 'name' => 'Spare'],
                ['value' => 'other', 'name' => 'Other'],
            ],
        ]);
    }

    public function store(StoreVehicleTyreRequest $request): RedirectResponse
    {
        $this->authorize('create', VehicleTyre::class);
        VehicleTyre::query()->create($request->validated());

        return to_route('fleet.vehicle-tyres.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle tyre created.']);
    }

    public function show(VehicleTyre $vehicle_tyre): Response
    {
        $this->authorize('view', $vehicle_tyre);
        $vehicle_tyre->load(['vehicle', 'tyreInventory']);

        return Inertia::render('Fleet/VehicleTyres/Show', ['vehicleTyre' => $vehicle_tyre]);
    }

    public function edit(VehicleTyre $vehicle_tyre): Response
    {
        $this->authorize('update', $vehicle_tyre);

        return Inertia::render('Fleet/VehicleTyres/Edit', [
            'vehicleTyre' => $vehicle_tyre,
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'tyreInventory' => TyreInventory::query()->orderBy('size')->get(['id', 'size', 'brand'])->map(fn ($t): array => ['id' => $t->id, 'label' => mb_trim($t->size.' '.($t->brand ?? ''))]),
            'positionOptions' => [
                ['value' => 'front_left', 'name' => 'Front left'],
                ['value' => 'front_right', 'name' => 'Front right'],
                ['value' => 'rear_left', 'name' => 'Rear left'],
                ['value' => 'rear_right', 'name' => 'Rear right'],
                ['value' => 'spare', 'name' => 'Spare'],
                ['value' => 'other', 'name' => 'Other'],
            ],
        ]);
    }

    public function update(UpdateVehicleTyreRequest $request, VehicleTyre $vehicle_tyre): RedirectResponse
    {
        $this->authorize('update', $vehicle_tyre);
        $vehicle_tyre->update($request->validated());

        return to_route('fleet.vehicle-tyres.show', $vehicle_tyre)->with('flash', ['status' => 'success', 'message' => 'Vehicle tyre updated.']);
    }

    public function destroy(VehicleTyre $vehicle_tyre): RedirectResponse
    {
        $this->authorize('delete', $vehicle_tyre);
        $vehicle_tyre->delete();

        return to_route('fleet.vehicle-tyres.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle tyre deleted.']);
    }
}

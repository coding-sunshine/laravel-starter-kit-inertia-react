<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreFuelStationRequest;
use App\Http\Requests\Fleet\UpdateFuelStationRequest;
use App\Models\Fleet\FuelStation;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class FuelStationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FuelStation::class);
        $orgId = TenantContext::id();
        $fuelStations = FuelStation::query()
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->when($request->boolean('is_active') !== null, fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/FuelStations/Index', [
            'fuelStations' => $fuelStations,
            'filters' => $request->only(['is_active']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', FuelStation::class);
        return Inertia::render('Fleet/FuelStations/Create');
    }

    public function store(StoreFuelStationRequest $request): RedirectResponse
    {
        $this->authorize('create', FuelStation::class);
        $validated = $request->validated();
        $validated['organization_id'] = TenantContext::id();
        FuelStation::create($validated);
        return to_route('fleet.fuel-stations.index')->with('flash', ['status' => 'success', 'message' => 'Fuel station created.']);
    }

    public function show(FuelStation $fuelStation): Response
    {
        $this->authorize('view', $fuelStation);
        return Inertia::render('Fleet/FuelStations/Show', ['fuelStation' => $fuelStation]);
    }

    public function edit(FuelStation $fuelStation): Response
    {
        $this->authorize('update', $fuelStation);
        return Inertia::render('Fleet/FuelStations/Edit', ['fuelStation' => $fuelStation]);
    }

    public function update(UpdateFuelStationRequest $request, FuelStation $fuelStation): RedirectResponse
    {
        $this->authorize('update', $fuelStation);
        $fuelStation->update($request->validated());
        return to_route('fleet.fuel-stations.show', $fuelStation)->with('flash', ['status' => 'success', 'message' => 'Fuel station updated.']);
    }

    public function destroy(FuelStation $fuelStation): RedirectResponse
    {
        $this->authorize('delete', $fuelStation);
        $fuelStation->delete();
        return to_route('fleet.fuel-stations.index')->with('flash', ['status' => 'success', 'message' => 'Fuel station deleted.']);
    }
}

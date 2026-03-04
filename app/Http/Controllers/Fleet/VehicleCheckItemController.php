<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\VehicleCheckItemResult;
use App\Enums\Fleet\VehicleCheckItemResultType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreVehicleCheckItemRequest;
use App\Http\Requests\Fleet\UpdateVehicleCheckItemRequest;
use App\Models\Fleet\VehicleCheck;
use App\Models\Fleet\VehicleCheckItem;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class VehicleCheckItemController extends Controller
{
    public function index(VehicleCheck $vehicle_check): Response
    {
        $this->authorize('view', $vehicle_check);
        $items = $vehicle_check->vehicleCheckItems()->orderBy('item_index')->get();

        return Inertia::render('Fleet/VehicleCheckItems/Index', [
            'vehicleCheck' => $vehicle_check,
            'vehicleCheckItems' => $items,
            'resultTypes' => array_map(fn (VehicleCheckItemResultType $c): array => ['value' => $c->value, 'name' => $c->name], VehicleCheckItemResultType::cases()),
            'results' => array_map(fn (VehicleCheckItemResult $c): array => ['value' => $c->value, 'name' => $c->name], VehicleCheckItemResult::cases()),
        ]);
    }

    public function create(VehicleCheck $vehicle_check): Response
    {
        $this->authorize('update', $vehicle_check);

        return Inertia::render('Fleet/VehicleCheckItems/Create', [
            'vehicleCheck' => $vehicle_check,
            'resultTypes' => array_map(fn (VehicleCheckItemResultType $c): array => ['value' => $c->value, 'name' => $c->name], VehicleCheckItemResultType::cases()),
            'results' => array_map(fn (VehicleCheckItemResult $c): array => ['value' => $c->value, 'name' => $c->name], VehicleCheckItemResult::cases()),
        ]);
    }

    public function store(StoreVehicleCheckItemRequest $request, VehicleCheck $vehicle_check): RedirectResponse
    {
        $this->authorize('update', $vehicle_check);
        $request->merge(['vehicle_check_id' => $vehicle_check->id]);
        VehicleCheckItem::query()->create($request->validated());

        return to_route('fleet.vehicle-checks.show', $vehicle_check)->with('flash', ['status' => 'success', 'message' => 'Check item created.']);
    }

    public function show(VehicleCheckItem $vehicle_check_item): Response
    {
        $this->authorize('view', $vehicle_check_item);
        $vehicle_check_item->load('vehicleCheck');

        return Inertia::render('Fleet/VehicleCheckItems/Show', ['vehicleCheckItem' => $vehicle_check_item]);
    }

    public function edit(VehicleCheckItem $vehicle_check_item): Response
    {
        $this->authorize('update', $vehicle_check_item);
        $vehicle_check_item->load('vehicleCheck');

        return Inertia::render('Fleet/VehicleCheckItems/Edit', [
            'vehicleCheckItem' => $vehicle_check_item,
            'resultTypes' => array_map(fn (VehicleCheckItemResultType $c): array => ['value' => $c->value, 'name' => $c->name], VehicleCheckItemResultType::cases()),
            'results' => array_map(fn (VehicleCheckItemResult $c): array => ['value' => $c->value, 'name' => $c->name], VehicleCheckItemResult::cases()),
        ]);
    }

    public function update(UpdateVehicleCheckItemRequest $request, VehicleCheckItem $vehicle_check_item): RedirectResponse
    {
        $this->authorize('update', $vehicle_check_item);
        $vehicle_check_item->update($request->validated());

        return to_route('fleet.vehicle-checks.show', $vehicle_check_item->vehicle_check_id)->with('flash', ['status' => 'success', 'message' => 'Check item updated.']);
    }

    public function destroy(VehicleCheckItem $vehicle_check_item): RedirectResponse
    {
        $this->authorize('delete', $vehicle_check_item);
        $vehicleCheckId = $vehicle_check_item->vehicle_check_id;
        $vehicle_check_item->delete();

        return to_route('fleet.vehicle-checks.show', $vehicleCheckId)->with('flash', ['status' => 'success', 'message' => 'Check item deleted.']);
    }
}

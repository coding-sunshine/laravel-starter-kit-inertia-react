<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StorePartsInventoryRequest;
use App\Http\Requests\Fleet\UpdatePartsInventoryRequest;
use App\Models\Fleet\PartsInventory;
use App\Models\Fleet\Garage;
use App\Models\Fleet\PartsSupplier;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class PartsInventoryController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', PartsInventory::class);
        $items = PartsInventory::query()->with(['garage', 'supplier'])->orderBy('part_number')->paginate(15)->withQueryString();

        return Inertia::render('Fleet/PartsInventory/Index', [
            'partsInventory' => $items,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PartsInventory::class);
        return Inertia::render('Fleet/PartsInventory/Create', [
            'garages' => Garage::query()->orderBy('name')->get(['id', 'name']),
            'partsSuppliers' => PartsSupplier::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StorePartsInventoryRequest $request): RedirectResponse
    {
        $this->authorize('create', PartsInventory::class);
        PartsInventory::create($request->validated());
        return to_route('fleet.parts-inventory.index')->with('flash', ['status' => 'success', 'message' => 'Parts inventory item created.']);
    }

    public function show(PartsInventory $parts_inventory): Response
    {
        $this->authorize('view', $parts_inventory);
        $parts_inventory->load(['garage', 'supplier']);
        return Inertia::render('Fleet/PartsInventory/Show', ['partsInventory' => $parts_inventory]);
    }

    public function edit(PartsInventory $parts_inventory): Response
    {
        $this->authorize('update', $parts_inventory);
        return Inertia::render('Fleet/PartsInventory/Edit', [
            'partsInventory' => $parts_inventory,
            'garages' => Garage::query()->orderBy('name')->get(['id', 'name']),
            'partsSuppliers' => PartsSupplier::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdatePartsInventoryRequest $request, PartsInventory $parts_inventory): RedirectResponse
    {
        $this->authorize('update', $parts_inventory);
        $parts_inventory->update($request->validated());
        return to_route('fleet.parts-inventory.show', $parts_inventory)->with('flash', ['status' => 'success', 'message' => 'Parts inventory item updated.']);
    }

    public function destroy(PartsInventory $parts_inventory): RedirectResponse
    {
        $this->authorize('delete', $parts_inventory);
        $parts_inventory->delete();
        return to_route('fleet.parts-inventory.index')->with('flash', ['status' => 'success', 'message' => 'Parts inventory item deleted.']);
    }
}

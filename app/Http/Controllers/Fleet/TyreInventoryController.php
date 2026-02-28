<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreTyreInventoryRequest;
use App\Http\Requests\Fleet\UpdateTyreInventoryRequest;
use App\Models\Fleet\TyreInventory;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class TyreInventoryController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', TyreInventory::class);
        $items = TyreInventory::query()->orderBy('size')->paginate(15)->withQueryString();

        return Inertia::render('Fleet/TyreInventory/Index', ['tyreInventory' => $items]);
    }

    public function create(): Response
    {
        $this->authorize('create', TyreInventory::class);
        return Inertia::render('Fleet/TyreInventory/Create');
    }

    public function store(StoreTyreInventoryRequest $request): RedirectResponse
    {
        $this->authorize('create', TyreInventory::class);
        TyreInventory::create($request->validated());
        return to_route('fleet.tyre-inventory.index')->with('flash', ['status' => 'success', 'message' => 'Tyre inventory item created.']);
    }

    public function show(TyreInventory $tyre_inventory): Response
    {
        $this->authorize('view', $tyre_inventory);
        return Inertia::render('Fleet/TyreInventory/Show', ['tyreInventory' => $tyre_inventory]);
    }

    public function edit(TyreInventory $tyre_inventory): Response
    {
        $this->authorize('update', $tyre_inventory);
        return Inertia::render('Fleet/TyreInventory/Edit', ['tyreInventory' => $tyre_inventory]);
    }

    public function update(UpdateTyreInventoryRequest $request, TyreInventory $tyre_inventory): RedirectResponse
    {
        $this->authorize('update', $tyre_inventory);
        $tyre_inventory->update($request->validated());
        return to_route('fleet.tyre-inventory.show', $tyre_inventory)->with('flash', ['status' => 'success', 'message' => 'Tyre inventory item updated.']);
    }

    public function destroy(TyreInventory $tyre_inventory): RedirectResponse
    {
        $this->authorize('delete', $tyre_inventory);
        $tyre_inventory->delete();
        return to_route('fleet.tyre-inventory.index')->with('flash', ['status' => 'success', 'message' => 'Tyre inventory item deleted.']);
    }
}

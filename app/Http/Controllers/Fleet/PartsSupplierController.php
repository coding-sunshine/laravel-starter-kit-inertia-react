<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StorePartsSupplierRequest;
use App\Http\Requests\Fleet\UpdatePartsSupplierRequest;
use App\Models\Fleet\PartsSupplier;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class PartsSupplierController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', PartsSupplier::class);
        $suppliers = PartsSupplier::query()->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('Fleet/PartsSuppliers/Index', ['partsSuppliers' => $suppliers]);
    }

    public function create(): Response
    {
        $this->authorize('create', PartsSupplier::class);
        return Inertia::render('Fleet/PartsSuppliers/Create');
    }

    public function store(StorePartsSupplierRequest $request): RedirectResponse
    {
        $this->authorize('create', PartsSupplier::class);
        PartsSupplier::create($request->validated());
        return to_route('fleet.parts-suppliers.index')->with('flash', ['status' => 'success', 'message' => 'Parts supplier created.']);
    }

    public function show(PartsSupplier $parts_supplier): Response
    {
        $this->authorize('view', $parts_supplier);
        return Inertia::render('Fleet/PartsSuppliers/Show', ['partsSupplier' => $parts_supplier]);
    }

    public function edit(PartsSupplier $parts_supplier): Response
    {
        $this->authorize('update', $parts_supplier);
        return Inertia::render('Fleet/PartsSuppliers/Edit', ['partsSupplier' => $parts_supplier]);
    }

    public function update(UpdatePartsSupplierRequest $request, PartsSupplier $parts_supplier): RedirectResponse
    {
        $this->authorize('update', $parts_supplier);
        $parts_supplier->update($request->validated());
        return to_route('fleet.parts-suppliers.show', $parts_supplier)->with('flash', ['status' => 'success', 'message' => 'Parts supplier updated.']);
    }

    public function destroy(PartsSupplier $parts_supplier): RedirectResponse
    {
        $this->authorize('delete', $parts_supplier);
        $parts_supplier->delete();
        return to_route('fleet.parts-suppliers.index')->with('flash', ['status' => 'success', 'message' => 'Parts supplier deleted.']);
    }
}

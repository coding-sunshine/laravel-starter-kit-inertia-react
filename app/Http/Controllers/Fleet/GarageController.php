<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreGarageRequest;
use App\Http\Requests\Fleet\UpdateGarageRequest;
use App\Models\Fleet\Garage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class GarageController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Garage::class);
        $garages = Garage::query()
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->boolean('is_active') !== null, fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/Garages/Index', [
            'garages' => $garages,
            'filters' => $request->only(['type', 'is_active']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Garage::class);
        return Inertia::render('Fleet/Garages/Create', [
            'types' => \App\Enums\Fleet\GarageType::cases(),
        ]);
    }

    public function store(StoreGarageRequest $request): RedirectResponse
    {
        $this->authorize('create', Garage::class);
        Garage::create($request->validated());
        return to_route('fleet.garages.index')->with('flash', ['status' => 'success', 'message' => 'Garage created.']);
    }

    public function show(Garage $garage): Response
    {
        $this->authorize('view', $garage);
        return Inertia::render('Fleet/Garages/Show', ['garage' => $garage]);
    }

    public function edit(Garage $garage): Response
    {
        $this->authorize('update', $garage);
        return Inertia::render('Fleet/Garages/Edit', [
            'garage' => $garage,
            'types' => \App\Enums\Fleet\GarageType::cases(),
        ]);
    }

    public function update(UpdateGarageRequest $request, Garage $garage): RedirectResponse
    {
        $this->authorize('update', $garage);
        $garage->update($request->validated());
        return to_route('fleet.garages.show', $garage)->with('flash', ['status' => 'success', 'message' => 'Garage updated.']);
    }

    public function destroy(Garage $garage): RedirectResponse
    {
        $this->authorize('delete', $garage);
        $garage->delete();
        return to_route('fleet.garages.index')->with('flash', ['status' => 'success', 'message' => 'Garage deleted.']);
    }
}

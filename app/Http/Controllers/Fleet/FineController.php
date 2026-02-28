<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreFineRequest;
use App\Http\Requests\Fleet\UpdateFineRequest;
use App\Models\Fleet\Fine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class FineController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Fine::class);
        $fines = Fine::query()
            ->with(['vehicle', 'driver'])
            ->when($request->input('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->input('driver_id'), fn ($q, $v) => $q->where('driver_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('offence_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/Fines/Index', [
            'fines' => $fines,
            'filters' => $request->only(['vehicle_id', 'driver_id', 'status']),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'fineTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\FineType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\FineStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Fine::class);

        return Inertia::render('Fleet/Fines/Create', [
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'fineTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\FineType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\FineStatus::cases()),
        ]);
    }

    public function store(StoreFineRequest $request): RedirectResponse
    {
        $this->authorize('create', Fine::class);
        Fine::create($request->validated());

        return to_route('fleet.fines.index')->with('flash', ['status' => 'success', 'message' => 'Fine created.']);
    }

    public function show(Fine $fine): Response
    {
        $this->authorize('view', $fine);
        $fine->load(['vehicle', 'driver']);

        return Inertia::render('Fleet/Fines/Show', ['fine' => $fine]);
    }

    public function edit(Fine $fine): Response
    {
        $this->authorize('update', $fine);

        return Inertia::render('Fleet/Fines/Edit', [
            'fine' => $fine,
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'fineTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\FineType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\FineStatus::cases()),
        ]);
    }

    public function update(UpdateFineRequest $request, Fine $fine): RedirectResponse
    {
        $this->authorize('update', $fine);
        $fine->update($request->validated());

        return to_route('fleet.fines.show', $fine)->with('flash', ['status' => 'success', 'message' => 'Fine updated.']);
    }

    public function destroy(Fine $fine): RedirectResponse
    {
        $this->authorize('delete', $fine);
        $fine->delete();

        return to_route('fleet.fines.index')->with('flash', ['status' => 'success', 'message' => 'Fine deleted.']);
    }
}

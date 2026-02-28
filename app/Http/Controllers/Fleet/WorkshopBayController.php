<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreWorkshopBayRequest;
use App\Http\Requests\Fleet\UpdateWorkshopBayRequest;
use App\Models\Fleet\WorkshopBay;
use App\Models\Fleet\Garage;
use App\Enums\Fleet\WorkshopBayStatus;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class WorkshopBayController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', WorkshopBay::class);
        $bays = WorkshopBay::query()->with('garage')->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('Fleet/WorkshopBays/Index', [
            'workshopBays' => $bays,
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], WorkshopBayStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', WorkshopBay::class);
        return Inertia::render('Fleet/WorkshopBays/Create', [
            'garages' => Garage::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], WorkshopBayStatus::cases()),
        ]);
    }

    public function store(StoreWorkshopBayRequest $request): RedirectResponse
    {
        $this->authorize('create', WorkshopBay::class);
        WorkshopBay::create($request->validated());
        return to_route('fleet.workshop-bays.index')->with('flash', ['status' => 'success', 'message' => 'Workshop bay created.']);
    }

    public function show(WorkshopBay $workshop_bay): Response
    {
        $this->authorize('view', $workshop_bay);
        $workshop_bay->load('garage');
        return Inertia::render('Fleet/WorkshopBays/Show', ['workshopBay' => $workshop_bay]);
    }

    public function edit(WorkshopBay $workshop_bay): Response
    {
        $this->authorize('update', $workshop_bay);
        return Inertia::render('Fleet/WorkshopBays/Edit', [
            'workshopBay' => $workshop_bay,
            'garages' => Garage::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], WorkshopBayStatus::cases()),
        ]);
    }

    public function update(UpdateWorkshopBayRequest $request, WorkshopBay $workshop_bay): RedirectResponse
    {
        $this->authorize('update', $workshop_bay);
        $workshop_bay->update($request->validated());
        return to_route('fleet.workshop-bays.show', $workshop_bay)->with('flash', ['status' => 'success', 'message' => 'Workshop bay updated.']);
    }

    public function destroy(WorkshopBay $workshop_bay): RedirectResponse
    {
        $this->authorize('delete', $workshop_bay);
        $workshop_bay->delete();
        return to_route('fleet.workshop-bays.index')->with('flash', ['status' => 'success', 'message' => 'Workshop bay deleted.']);
    }
}

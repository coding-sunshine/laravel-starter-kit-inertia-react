<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\PermitToWorkStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StorePermitToWorkRequest;
use App\Http\Requests\Fleet\UpdatePermitToWorkRequest;
use App\Models\Fleet\Location;
use App\Models\Fleet\PermitToWork;
use App\Models\Fleet\Vehicle;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PermitToWorkController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PermitToWork::class);
        $permits = PermitToWork::query()
            ->with(['issuedBy', 'issuedTo', 'location', 'vehicle'])
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('valid_from')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/PermitToWork/Index', [
            'permitToWorkList' => $permits,
            'filters' => $request->only(['status']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], PermitToWorkStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PermitToWork::class);
        $users = User::query()->orderBy('name')->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);
        $locations = Location::query()->orderBy('name')->get(['id', 'name'])->map(fn ($l) => ['id' => $l->id, 'name' => $l->name]);
        $vehicles = Vehicle::query()->orderBy('registration')->get(['id', 'registration'])->map(fn ($v) => ['id' => $v->id, 'name' => $v->registration]);

        return Inertia::render('Fleet/PermitToWork/Create', [
            'users' => $users,
            'locations' => $locations,
            'vehicles' => $vehicles,
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], PermitToWorkStatus::cases()),
        ]);
    }

    public function store(StorePermitToWorkRequest $request): RedirectResponse
    {
        $this->authorize('create', PermitToWork::class);
        PermitToWork::create($request->validated());
        return to_route('fleet.permit-to-work.index')->with('flash', ['status' => 'success', 'message' => 'Permit to work created.']);
    }

    public function show(PermitToWork $permit_to_work): Response
    {
        $this->authorize('view', $permit_to_work);
        $permit_to_work->load(['issuedBy', 'issuedTo', 'location', 'vehicle']);
        return Inertia::render('Fleet/PermitToWork/Show', ['permitToWork' => $permit_to_work]);
    }

    public function edit(PermitToWork $permit_to_work): Response
    {
        $this->authorize('update', $permit_to_work);
        $users = User::query()->orderBy('name')->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name]);
        $locations = Location::query()->orderBy('name')->get(['id', 'name'])->map(fn ($l) => ['id' => $l->id, 'name' => $l->name]);
        $vehicles = Vehicle::query()->orderBy('registration')->get(['id', 'registration'])->map(fn ($v) => ['id' => $v->id, 'name' => $v->registration]);

        return Inertia::render('Fleet/PermitToWork/Edit', [
            'permitToWork' => $permit_to_work,
            'users' => $users,
            'locations' => $locations,
            'vehicles' => $vehicles,
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], PermitToWorkStatus::cases()),
        ]);
    }

    public function update(UpdatePermitToWorkRequest $request, PermitToWork $permit_to_work): RedirectResponse
    {
        $this->authorize('update', $permit_to_work);
        $permit_to_work->update($request->validated());
        return to_route('fleet.permit-to-work.show', $permit_to_work)->with('flash', ['status' => 'success', 'message' => 'Permit to work updated.']);
    }

    public function destroy(PermitToWork $permit_to_work): RedirectResponse
    {
        $this->authorize('delete', $permit_to_work);
        $permit_to_work->delete();
        return to_route('fleet.permit-to-work.index')->with('flash', ['status' => 'success', 'message' => 'Permit to work deleted.']);
    }
}

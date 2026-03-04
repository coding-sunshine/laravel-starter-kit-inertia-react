<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\PpeAssignmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StorePpeAssignmentRequest;
use App\Http\Requests\Fleet\UpdatePpeAssignmentRequest;
use App\Models\Fleet\Driver;
use App\Models\Fleet\PpeAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PpeAssignmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', PpeAssignment::class);
        $assignments = PpeAssignment::query()
            ->with(['user', 'driver'])
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->latest('issued_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/PpeAssignments/Index', [
            'ppeAssignments' => $assignments,
            'filters' => $request->only(['status']),
            'statuses' => array_map(fn (PpeAssignmentStatus $c): array => ['value' => $c->value, 'name' => $c->name], PpeAssignmentStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', PpeAssignment::class);
        $users = User::query()->orderBy('name')->get(['id', 'name'])->map(fn ($u): array => ['id' => $u->id, 'name' => $u->name]);
        $drivers = Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]);

        return Inertia::render('Fleet/PpeAssignments/Create', [
            'users' => $users,
            'drivers' => $drivers,
            'statuses' => array_map(fn (PpeAssignmentStatus $c): array => ['value' => $c->value, 'name' => $c->name], PpeAssignmentStatus::cases()),
        ]);
    }

    public function store(StorePpeAssignmentRequest $request): RedirectResponse
    {
        $this->authorize('create', PpeAssignment::class);
        PpeAssignment::query()->create($request->validated());

        return to_route('fleet.ppe-assignments.index')->with('flash', ['status' => 'success', 'message' => 'PPE assignment created.']);
    }

    public function show(PpeAssignment $ppe_assignment): Response
    {
        $this->authorize('view', $ppe_assignment);
        $ppe_assignment->load(['user', 'driver']);

        return Inertia::render('Fleet/PpeAssignments/Show', ['ppeAssignment' => $ppe_assignment]);
    }

    public function edit(PpeAssignment $ppe_assignment): Response
    {
        $this->authorize('update', $ppe_assignment);
        $users = User::query()->orderBy('name')->get(['id', 'name'])->map(fn ($u): array => ['id' => $u->id, 'name' => $u->name]);
        $drivers = Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]);

        return Inertia::render('Fleet/PpeAssignments/Edit', [
            'ppeAssignment' => $ppe_assignment,
            'users' => $users,
            'drivers' => $drivers,
            'statuses' => array_map(fn (PpeAssignmentStatus $c): array => ['value' => $c->value, 'name' => $c->name], PpeAssignmentStatus::cases()),
        ]);
    }

    public function update(UpdatePpeAssignmentRequest $request, PpeAssignment $ppe_assignment): RedirectResponse
    {
        $this->authorize('update', $ppe_assignment);
        $ppe_assignment->update($request->validated());

        return to_route('fleet.ppe-assignments.show', $ppe_assignment)->with('flash', ['status' => 'success', 'message' => 'PPE assignment updated.']);
    }

    public function destroy(PpeAssignment $ppe_assignment): RedirectResponse
    {
        $this->authorize('delete', $ppe_assignment);
        $ppe_assignment->delete();

        return to_route('fleet.ppe-assignments.index')->with('flash', ['status' => 'success', 'message' => 'PPE assignment deleted.']);
    }
}

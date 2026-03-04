<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreOperatorLicenceRequest;
use App\Http\Requests\Fleet\UpdateOperatorLicenceRequest;
use App\Models\Fleet\OperatorLicence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class OperatorLicenceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', OperatorLicence::class);
        $operatorLicences = OperatorLicence::query()
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('license_number')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/OperatorLicences/Index', [
            'operatorLicences' => $operatorLicences,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', OperatorLicence::class);

        return Inertia::render('Fleet/OperatorLicences/Create', [
            'statuses' => array_map(fn (\App\Enums\Fleet\OperatorLicenceStatus $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\OperatorLicenceStatus::cases()),
        ]);
    }

    public function store(StoreOperatorLicenceRequest $request): RedirectResponse
    {
        $this->authorize('create', OperatorLicence::class);
        OperatorLicence::query()->create($request->validated());

        return to_route('fleet.operator-licences.index')->with('flash', ['status' => 'success', 'message' => 'Operator licence created.']);
    }

    public function show(OperatorLicence $operatorLicence): Response
    {
        $this->authorize('view', $operatorLicence);

        return Inertia::render('Fleet/OperatorLicences/Show', ['operatorLicence' => $operatorLicence]);
    }

    public function edit(OperatorLicence $operatorLicence): Response
    {
        $this->authorize('update', $operatorLicence);

        return Inertia::render('Fleet/OperatorLicences/Edit', [
            'operatorLicence' => $operatorLicence,
            'statuses' => array_map(fn (\App\Enums\Fleet\OperatorLicenceStatus $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\OperatorLicenceStatus::cases()),
        ]);
    }

    public function update(UpdateOperatorLicenceRequest $request, OperatorLicence $operatorLicence): RedirectResponse
    {
        $this->authorize('update', $operatorLicence);
        $operatorLicence->update($request->validated());

        return to_route('fleet.operator-licences.show', $operatorLicence)->with('flash', ['status' => 'success', 'message' => 'Operator licence updated.']);
    }

    public function destroy(OperatorLicence $operatorLicence): RedirectResponse
    {
        $this->authorize('delete', $operatorLicence);
        $operatorLicence->delete();

        return to_route('fleet.operator-licences.index')->with('flash', ['status' => 'success', 'message' => 'Operator licence deleted.']);
    }
}

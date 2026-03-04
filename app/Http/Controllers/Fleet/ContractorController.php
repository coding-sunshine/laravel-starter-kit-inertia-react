<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\ContractorStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreContractorRequest;
use App\Http\Requests\Fleet\UpdateContractorRequest;
use App\Models\Fleet\Contractor;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ContractorController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Contractor::class);
        $contractors = Contractor::query()->orderBy('name')->paginate(15)->withQueryString();

        return Inertia::render('Fleet/Contractors/Index', [
            'contractors' => $contractors,
            'statuses' => array_map(fn (ContractorStatus $c): array => ['value' => $c->value, 'name' => $c->name], ContractorStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Contractor::class);

        return Inertia::render('Fleet/Contractors/Create', [
            'statuses' => array_map(fn (ContractorStatus $c): array => ['value' => $c->value, 'name' => $c->name], ContractorStatus::cases()),
        ]);
    }

    public function store(StoreContractorRequest $request): RedirectResponse
    {
        $this->authorize('create', Contractor::class);
        Contractor::query()->create($request->validated());

        return to_route('fleet.contractors.index')->with('flash', ['status' => 'success', 'message' => 'Contractor created.']);
    }

    public function show(Contractor $contractor): Response
    {
        $this->authorize('view', $contractor);

        return Inertia::render('Fleet/Contractors/Show', ['contractor' => $contractor]);
    }

    public function edit(Contractor $contractor): Response
    {
        $this->authorize('update', $contractor);

        return Inertia::render('Fleet/Contractors/Edit', [
            'contractor' => $contractor,
            'statuses' => array_map(fn (ContractorStatus $c): array => ['value' => $c->value, 'name' => $c->name], ContractorStatus::cases()),
        ]);
    }

    public function update(UpdateContractorRequest $request, Contractor $contractor): RedirectResponse
    {
        $this->authorize('update', $contractor);
        $contractor->update($request->validated());

        return to_route('fleet.contractors.show', $contractor)->with('flash', ['status' => 'success', 'message' => 'Contractor updated.']);
    }

    public function destroy(Contractor $contractor): RedirectResponse
    {
        $this->authorize('delete', $contractor);
        $contractor->delete();

        return to_route('fleet.contractors.index')->with('flash', ['status' => 'success', 'message' => 'Contractor deleted.']);
    }
}

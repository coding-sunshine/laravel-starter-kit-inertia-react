<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\ContractorComplianceStatus;
use App\Enums\Fleet\ContractorComplianceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreContractorComplianceRequest;
use App\Http\Requests\Fleet\UpdateContractorComplianceRequest;
use App\Models\Fleet\Contractor;
use App\Models\Fleet\ContractorCompliance;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ContractorComplianceController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', ContractorCompliance::class);
        $items = ContractorCompliance::query()->with('contractor')->latest('expiry_date')->paginate(15)->withQueryString();

        return Inertia::render('Fleet/ContractorCompliance/Index', [
            'contractorCompliance' => $items,
            'contractors' => Contractor::query()->orderBy('name')->get(['id', 'name']),
            'complianceTypes' => array_map(fn (ContractorComplianceType $c): array => ['value' => $c->value, 'name' => $c->name], ContractorComplianceType::cases()),
            'statuses' => array_map(fn (ContractorComplianceStatus $c): array => ['value' => $c->value, 'name' => $c->name], ContractorComplianceStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', ContractorCompliance::class);

        return Inertia::render('Fleet/ContractorCompliance/Create', [
            'contractors' => Contractor::query()->orderBy('name')->get(['id', 'name']),
            'complianceTypes' => array_map(fn (ContractorComplianceType $c): array => ['value' => $c->value, 'name' => $c->name], ContractorComplianceType::cases()),
            'statuses' => array_map(fn (ContractorComplianceStatus $c): array => ['value' => $c->value, 'name' => $c->name], ContractorComplianceStatus::cases()),
        ]);
    }

    public function store(StoreContractorComplianceRequest $request): RedirectResponse
    {
        $this->authorize('create', ContractorCompliance::class);
        ContractorCompliance::query()->create($request->validated());

        return to_route('fleet.contractor-compliance.index')->with('flash', ['status' => 'success', 'message' => 'Contractor compliance created.']);
    }

    public function show(ContractorCompliance $contractor_compliance): Response
    {
        $this->authorize('view', $contractor_compliance);
        $contractor_compliance->load('contractor');

        return Inertia::render('Fleet/ContractorCompliance/Show', ['contractorCompliance' => $contractor_compliance]);
    }

    public function edit(ContractorCompliance $contractor_compliance): Response
    {
        $this->authorize('update', $contractor_compliance);

        return Inertia::render('Fleet/ContractorCompliance/Edit', [
            'contractorCompliance' => $contractor_compliance,
            'contractors' => Contractor::query()->orderBy('name')->get(['id', 'name']),
            'complianceTypes' => array_map(fn (ContractorComplianceType $c): array => ['value' => $c->value, 'name' => $c->name], ContractorComplianceType::cases()),
            'statuses' => array_map(fn (ContractorComplianceStatus $c): array => ['value' => $c->value, 'name' => $c->name], ContractorComplianceStatus::cases()),
        ]);
    }

    public function update(UpdateContractorComplianceRequest $request, ContractorCompliance $contractor_compliance): RedirectResponse
    {
        $this->authorize('update', $contractor_compliance);
        $contractor_compliance->update($request->validated());

        return to_route('fleet.contractor-compliance.show', $contractor_compliance)->with('flash', ['status' => 'success', 'message' => 'Contractor compliance updated.']);
    }

    public function destroy(ContractorCompliance $contractor_compliance): RedirectResponse
    {
        $this->authorize('delete', $contractor_compliance);
        $contractor_compliance->delete();

        return to_route('fleet.contractor-compliance.index')->with('flash', ['status' => 'success', 'message' => 'Contractor compliance deleted.']);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreInsuranceClaimRequest;
use App\Http\Requests\Fleet\UpdateInsuranceClaimRequest;
use App\Models\Fleet\Incident;
use App\Models\Fleet\InsuranceClaim;
use App\Models\Fleet\InsurancePolicy;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class InsuranceClaimController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', InsuranceClaim::class);
        $claims = InsuranceClaim::query()
            ->with(['incident', 'insurancePolicy'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/InsuranceClaims/Index', [
            'insuranceClaims' => $claims,
            'incidents' => Incident::query()->orderByDesc('incident_timestamp')->get(['id', 'incident_number']),
            'insurancePolicies' => InsurancePolicy::query()->orderBy('policy_number')->get(['id', 'policy_number']),
            'claimTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\InsuranceClaimType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\InsuranceClaimStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', InsuranceClaim::class);
        return Inertia::render('Fleet/InsuranceClaims/Create', [
            'incidents' => Incident::query()->orderByDesc('incident_timestamp')->get(['id', 'incident_number']),
            'insurancePolicies' => InsurancePolicy::query()->orderBy('policy_number')->get(['id', 'policy_number']),
            'claimTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\InsuranceClaimType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\InsuranceClaimStatus::cases()),
        ]);
    }

    public function store(StoreInsuranceClaimRequest $request): RedirectResponse
    {
        $this->authorize('create', InsuranceClaim::class);
        $claim = InsuranceClaim::create($request->validated());
        return to_route('fleet.insurance-claims.index')->with('flash', ['status' => 'success', 'message' => 'Insurance claim created.']);
    }

    public function show(InsuranceClaim $insurance_claim): Response
    {
        $this->authorize('view', $insurance_claim);
        $insurance_claim->load(['incident', 'insurancePolicy']);

        return Inertia::render('Fleet/InsuranceClaims/Show', [
            'insuranceClaim' => $insurance_claim,
            'incidents' => Incident::query()->orderByDesc('incident_timestamp')->get(['id', 'incident_number']),
            'insurancePolicies' => InsurancePolicy::query()->orderBy('policy_number')->get(['id', 'policy_number']),
            'claimTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\InsuranceClaimType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\InsuranceClaimStatus::cases()),
        ]);
    }

    public function edit(InsuranceClaim $insurance_claim): Response
    {
        $this->authorize('update', $insurance_claim);
        return Inertia::render('Fleet/InsuranceClaims/Edit', [
            'insuranceClaim' => $insurance_claim,
            'incidents' => Incident::query()->orderByDesc('incident_timestamp')->get(['id', 'incident_number']),
            'insurancePolicies' => InsurancePolicy::query()->orderBy('policy_number')->get(['id', 'policy_number']),
            'claimTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\InsuranceClaimType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\InsuranceClaimStatus::cases()),
        ]);
    }

    public function update(UpdateInsuranceClaimRequest $request, InsuranceClaim $insurance_claim): RedirectResponse
    {
        $this->authorize('update', $insurance_claim);
        $insurance_claim->update($request->validated());
        return to_route('fleet.insurance-claims.show', $insurance_claim)->with('flash', ['status' => 'success', 'message' => 'Insurance claim updated.']);
    }

    public function destroy(InsuranceClaim $insurance_claim): RedirectResponse
    {
        $this->authorize('delete', $insurance_claim);
        $insurance_claim->delete();
        return to_route('fleet.insurance-claims.index')->with('flash', ['status' => 'success', 'message' => 'Insurance claim deleted.']);
    }
}

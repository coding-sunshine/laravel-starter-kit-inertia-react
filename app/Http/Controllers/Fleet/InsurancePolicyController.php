<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreInsurancePolicyRequest;
use App\Http\Requests\Fleet\UpdateInsurancePolicyRequest;
use App\Models\Fleet\InsurancePolicy;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class InsurancePolicyController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', InsurancePolicy::class);
        $policies = InsurancePolicy::query()
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/InsurancePolicies/Index', [
            'insurancePolicies' => $policies,
            'policyTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\InsurancePolicyType::cases()),
            'coverageTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\CoverageType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\InsurancePolicyStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', InsurancePolicy::class);
        return Inertia::render('Fleet/InsurancePolicies/Create', [
            'policyTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\InsurancePolicyType::cases()),
            'coverageTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\CoverageType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\InsurancePolicyStatus::cases()),
        ]);
    }

    public function store(StoreInsurancePolicyRequest $request): RedirectResponse
    {
        $this->authorize('create', InsurancePolicy::class);
        $policy = InsurancePolicy::create($request->validated());
        return to_route('fleet.insurance-policies.index')->with('flash', ['status' => 'success', 'message' => 'Insurance policy created.']);
    }

    public function show(InsurancePolicy $insurance_policy): Response
    {
        $this->authorize('view', $insurance_policy);
        return Inertia::render('Fleet/InsurancePolicies/Show', ['insurancePolicy' => $insurance_policy]);
    }

    public function edit(InsurancePolicy $insurance_policy): Response
    {
        $this->authorize('update', $insurance_policy);
        return Inertia::render('Fleet/InsurancePolicies/Edit', [
            'insurancePolicy' => $insurance_policy,
            'policyTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\InsurancePolicyType::cases()),
            'coverageTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\CoverageType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\InsurancePolicyStatus::cases()),
        ]);
    }

    public function update(UpdateInsurancePolicyRequest $request, InsurancePolicy $insurance_policy): RedirectResponse
    {
        $this->authorize('update', $insurance_policy);
        $insurance_policy->update($request->validated());
        return to_route('fleet.insurance-policies.show', $insurance_policy)->with('flash', ['status' => 'success', 'message' => 'Insurance policy updated.']);
    }

    public function destroy(InsurancePolicy $insurance_policy): RedirectResponse
    {
        $this->authorize('delete', $insurance_policy);
        $insurance_policy->delete();
        return to_route('fleet.insurance-policies.index')->with('flash', ['status' => 'success', 'message' => 'Insurance policy deleted.']);
    }
}

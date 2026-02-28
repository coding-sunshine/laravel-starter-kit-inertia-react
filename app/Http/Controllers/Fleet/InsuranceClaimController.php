<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreInsuranceClaimRequest;
use App\Http\Requests\Fleet\UpdateInsuranceClaimRequest;
use App\Jobs\Ai\RunDamageAssessmentJob;
use App\Models\Fleet\AiAnalysisResult;
use App\Models\Fleet\Incident;
use App\Models\Fleet\InsuranceClaim;
use App\Models\Fleet\InsurancePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $validated = $request->validated();
        unset($validated['photos']);
        $claim = InsuranceClaim::create($validated);
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $claim->addMedia($file)->toMediaCollection('photos');
            }
            RunDamageAssessmentJob::dispatch('insurance_claim', $claim->id, $request->user()?->id);
        }
        return to_route('fleet.insurance-claims.index')->with('flash', ['status' => 'success', 'message' => 'Insurance claim created.']);
    }

    public function show(InsuranceClaim $insurance_claim): Response
    {
        $this->authorize('view', $insurance_claim);
        $insurance_claim->load(['incident', 'insurancePolicy']);
        $insurance_claim->loadMedia('photos');
        $photoUrls = $insurance_claim->getMedia('photos')->map(fn ($m) => ['id' => $m->id, 'url' => $m->getUrl()])->values()->all();

        $damageAnalysis = AiAnalysisResult::query()
            ->where('entity_type', 'insurance_claim')
            ->where('entity_id', $insurance_claim->id)
            ->where('analysis_type', 'claims_processing')
            ->orderByDesc('created_at')
            ->first();

        return Inertia::render('Fleet/InsuranceClaims/Show', [
            'insuranceClaim' => $insurance_claim,
            'photoUrls' => $photoUrls,
            'damageAnalysis' => $damageAnalysis?->only(['id', 'primary_finding', 'detailed_analysis', 'recommendations', 'priority', 'confidence_score', 'created_at']),
            'runDamageAssessmentUrl' => route('fleet.insurance-claims.run-damage-assessment', $insurance_claim),
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
        $validated = $request->validated();
        unset($validated['photos']);
        $insurance_claim->update($validated);
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $insurance_claim->addMedia($file)->toMediaCollection('photos');
            }
            RunDamageAssessmentJob::dispatch('insurance_claim', $insurance_claim->id, $request->user()?->id);
        }
        return to_route('fleet.insurance-claims.show', $insurance_claim)->with('flash', ['status' => 'success', 'message' => 'Insurance claim updated.']);
    }

    public function destroy(InsuranceClaim $insurance_claim): RedirectResponse
    {
        $this->authorize('delete', $insurance_claim);
        $insurance_claim->delete();
        return to_route('fleet.insurance-claims.index')->with('flash', ['status' => 'success', 'message' => 'Insurance claim deleted.']);
    }

    /**
     * Run AI damage/claims assessment on the claim's first photo. Queued; returns immediately.
     */
    public function runDamageAssessment(Request $request, InsuranceClaim $insurance_claim): JsonResponse
    {
        $this->authorize('update', $insurance_claim);
        $insurance_claim->loadMedia('photos');
        if ($insurance_claim->getFirstMedia('photos') === null) {
            return response()->json(['message' => 'No photo to analyze.', 'result_id' => null], 422);
        }
        RunDamageAssessmentJob::dispatch('insurance_claim', $insurance_claim->id, $request->user()?->id);
        return response()->json([
            'message' => 'Claims analysis queued. Results will appear in AI Analysis and on this claim once complete.',
            'result_id' => null,
        ]);
    }
}

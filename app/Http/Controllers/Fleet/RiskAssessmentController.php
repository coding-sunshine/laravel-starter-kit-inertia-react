<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\RiskAssessmentStatus;
use App\Enums\Fleet\RiskAssessmentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreRiskAssessmentRequest;
use App\Http\Requests\Fleet\UpdateRiskAssessmentRequest;
use App\Models\Fleet\Driver;
use App\Models\Fleet\Location;
use App\Models\Fleet\RiskAssessment;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class RiskAssessmentController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', RiskAssessment::class);
        $assessments = RiskAssessment::query()
            ->with(['subject', 'createdByUser'])
            ->when($request->input('type'), fn ($q, $v) => $q->where('type', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/RiskAssessments/Index', [
            'riskAssessments' => $assessments,
            'filters' => $request->only(['type', 'status']),
            'types' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], RiskAssessmentType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], RiskAssessmentStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', RiskAssessment::class);
        $vehicles = Vehicle::query()->orderBy('registration')->get(['id', 'registration'])->map(fn ($v) => ['id' => $v->id, 'name' => $v->registration, 'type' => 'vehicle']);
        $drivers = Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d) => ['id' => $d->id, 'name' => $d->first_name . ' ' . $d->last_name, 'type' => 'driver']);
        $locations = Location::query()->orderBy('name')->get(['id', 'name'])->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'type' => 'location']);
        $subjectOptions = $vehicles->concat($drivers)->concat($locations)->values()->all();

        return Inertia::render('Fleet/RiskAssessments/Create', [
            'types' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], RiskAssessmentType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], RiskAssessmentStatus::cases()),
            'subjectOptions' => $subjectOptions,
        ]);
    }

    public function store(StoreRiskAssessmentRequest $request): RedirectResponse
    {
        $this->authorize('create', RiskAssessment::class);
        RiskAssessment::create($request->validated());
        return to_route('fleet.risk-assessments.index')->with('flash', ['status' => 'success', 'message' => 'Risk assessment created.']);
    }

    public function show(RiskAssessment $risk_assessment): Response
    {
        $this->authorize('view', $risk_assessment);
        $risk_assessment->load(['subject', 'createdByUser', 'approvedBy']);
        return Inertia::render('Fleet/RiskAssessments/Show', ['riskAssessment' => $risk_assessment]);
    }

    public function edit(RiskAssessment $risk_assessment): Response
    {
        $this->authorize('update', $risk_assessment);
        $vehicles = Vehicle::query()->orderBy('registration')->get(['id', 'registration'])->map(fn ($v) => ['id' => $v->id, 'name' => $v->registration, 'type' => 'vehicle']);
        $drivers = Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d) => ['id' => $d->id, 'name' => $d->first_name . ' ' . $d->last_name, 'type' => 'driver']);
        $locations = Location::query()->orderBy('name')->get(['id', 'name'])->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'type' => 'location']);
        $subjectOptions = $vehicles->concat($drivers)->concat($locations)->values()->all();

        return Inertia::render('Fleet/RiskAssessments/Edit', [
            'riskAssessment' => $risk_assessment,
            'types' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], RiskAssessmentType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], RiskAssessmentStatus::cases()),
            'subjectOptions' => $subjectOptions,
        ]);
    }

    public function update(UpdateRiskAssessmentRequest $request, RiskAssessment $risk_assessment): RedirectResponse
    {
        $this->authorize('update', $risk_assessment);
        $risk_assessment->update($request->validated());
        return to_route('fleet.risk-assessments.show', $risk_assessment)->with('flash', ['status' => 'success', 'message' => 'Risk assessment updated.']);
    }

    public function destroy(RiskAssessment $risk_assessment): RedirectResponse
    {
        $this->authorize('delete', $risk_assessment);
        $risk_assessment->delete();
        return to_route('fleet.risk-assessments.index')->with('flash', ['status' => 'success', 'message' => 'Risk assessment deleted.']);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreDefectRequest;
use App\Http\Requests\Fleet\UpdateDefectRequest;
use App\Jobs\Ai\RunDamageAssessmentJob;
use App\Models\Fleet\AiAnalysisResult;
use App\Models\Fleet\Defect;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DefectController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Defect::class);
        $defects = Defect::query()
            ->with(['vehicle', 'reportedByDriver', 'workOrder'])
            ->when($request->input('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->when($request->input('severity'), fn ($q, $v) => $q->where('severity', $v))
            ->orderByDesc('reported_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/Defects/Index', [
            'defects' => $defects,
            'filters' => $request->only(['vehicle_id', 'status', 'severity']),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\DefectStatus::cases()),
            'severities' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\DefectSeverity::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Defect::class);
        return Inertia::render('Fleet/Defects/Create', [
            'categories' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\DefectCategory::cases()),
            'severities' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\DefectSeverity::cases()),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'workOrders' => \App\Models\Fleet\WorkOrder::query()->orderByDesc('created_at')->limit(100)->get(['id', 'work_order_number', 'title']),
        ]);
    }

    public function store(StoreDefectRequest $request): RedirectResponse
    {
        $this->authorize('create', Defect::class);
        $validated = $request->validated();
        unset($validated['photos']);
        $defect = Defect::create($validated);
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $defect->addMedia($file)->toMediaCollection('photos');
            }
            RunDamageAssessmentJob::dispatch('defect', $defect->id, $request->user()?->id);
        }
        return to_route('fleet.defects.index')->with('flash', ['status' => 'success', 'message' => 'Defect created.']);
    }

    public function show(Defect $defect): Response
    {
        $this->authorize('view', $defect);
        $defect->load(['vehicle', 'reportedByDriver', 'reportedByUser', 'workOrder']);
        $defect->loadMedia('photos');
        $photoUrls = $defect->getMedia('photos')->map(fn ($m) => ['id' => $m->id, 'url' => $m->getUrl()])->values()->all();
        $damageAnalysis = AiAnalysisResult::query()
            ->where('entity_type', 'defect')
            ->where('entity_id', $defect->id)
            ->where('analysis_type', 'damage_detection')
            ->orderByDesc('created_at')
            ->first();

        return Inertia::render('Fleet/Defects/Show', [
            'defect' => $defect,
            'photoUrls' => $photoUrls,
            'damageAnalysis' => $damageAnalysis?->only(['id', 'primary_finding', 'detailed_analysis', 'recommendations', 'priority', 'confidence_score', 'created_at']),
            'runDamageAssessmentUrl' => route('fleet.defects.run-damage-assessment', $defect),
        ]);
    }

    public function edit(Defect $defect): Response
    {
        $this->authorize('update', $defect);
        $defect->loadMedia('photos');

        return Inertia::render('Fleet/Defects/Edit', [
            'defect' => $defect,
            'categories' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\DefectCategory::cases()),
            'severities' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\DefectSeverity::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\DefectStatus::cases()),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'workOrders' => \App\Models\Fleet\WorkOrder::query()->orderByDesc('created_at')->limit(100)->get(['id', 'work_order_number', 'title']),
        ]);
    }

    public function update(UpdateDefectRequest $request, Defect $defect): RedirectResponse
    {
        $this->authorize('update', $defect);
        $validated = $request->validated();
        unset($validated['photos']);
        $defect->update($validated);
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $defect->addMedia($file)->toMediaCollection('photos');
            }
            RunDamageAssessmentJob::dispatch('defect', $defect->id, $request->user()?->id);
        }
        return to_route('fleet.defects.show', $defect)->with('flash', ['status' => 'success', 'message' => 'Defect updated.']);
    }

    public function destroy(Defect $defect): RedirectResponse
    {
        $this->authorize('delete', $defect);
        $defect->delete();
        return to_route('fleet.defects.index')->with('flash', ['status' => 'success', 'message' => 'Defect deleted.']);
    }

    /**
     * Run AI damage assessment on the defect's first photo. Queued; returns immediately.
     */
    public function runDamageAssessment(Request $request, Defect $defect): JsonResponse
    {
        $this->authorize('update', $defect);
        $defect->loadMedia('photos');
        if ($defect->getFirstMedia('photos') === null) {
            return response()->json(['message' => 'No photo to analyze.', 'result_id' => null], 422);
        }
        RunDamageAssessmentJob::dispatch('defect', $defect->id, $request->user()?->id);
        return response()->json([
            'message' => 'Damage analysis queued. Results will appear in AI Analysis and on this defect once complete.',
            'result_id' => null,
        ]);
    }
}

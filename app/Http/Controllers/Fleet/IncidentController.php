<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreIncidentRequest;
use App\Http\Requests\Fleet\UpdateIncidentRequest;
use App\Jobs\Ai\RunDamageAssessmentJob;
use App\Jobs\Ai\RunIncidentAnalysisJob;
use App\Models\Fleet\AiAnalysisResult;
use App\Models\Fleet\Driver;
use App\Models\Fleet\Incident;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class IncidentController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Incident::class);
        $incidents = Incident::query()
            ->with(['vehicle', 'driver'])
            ->orderByDesc('incident_timestamp')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/Incidents/Index', [
            'incidents' => $incidents,
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            ...$this->enumOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Incident::class);

        return Inertia::render('Fleet/Incidents/Create', [
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            ...$this->enumOptions(),
        ]);
    }

    public function store(StoreIncidentRequest $request): RedirectResponse
    {
        $this->authorize('create', Incident::class);
        $validated = $request->validated();
        unset($validated['photos']);
        $validated['incident_timestamp'] = \Illuminate\Support\Facades\Date::parse($validated['incident_date'].' '.$validated['incident_time']);
        $incident = Incident::query()->create($validated);
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $incident->addMedia($file)->toMediaCollection('photos');
            }
            dispatch(new RunDamageAssessmentJob('incident', $incident->id, $request->user()?->id));
        }
        if (! in_array(mb_trim((string) ($validated['description'] ?? '')), ['', '0'], true)) {
            dispatch(new RunIncidentAnalysisJob($incident->id, $request->user()?->id));
        }

        return to_route('fleet.incidents.index')->with('flash', ['status' => 'success', 'message' => 'Incident created.']);
    }

    public function show(Incident $incident): Response
    {
        $this->authorize('view', $incident);
        $incident->load(['vehicle', 'driver', 'reportedByUser', 'investigatingOfficerUser']);
        $incident->loadMedia('photos');
        $mediaItems = $incident->getMedia('photos')->map(fn ($m): array => [
            'id' => $m->id,
            'url' => $m->getUrl(),
            'mime_type' => $m->mime_type ?? '',
            'file_name' => $m->file_name ?? 'file',
        ])->values()->all();

        $damageAnalysis = AiAnalysisResult::query()
            ->where('entity_type', 'incident')
            ->where('entity_id', $incident->id)
            ->where('analysis_type', 'damage_detection')->latest()
            ->first();

        $incidentAnalysis = AiAnalysisResult::query()
            ->where('entity_type', 'incident')
            ->where('entity_id', $incident->id)
            ->where('analysis_type', 'incident_analysis')->latest()
            ->first();

        return Inertia::render('Fleet/Incidents/Show', [
            'incident' => $incident,
            'mediaItems' => $mediaItems,
            'damageAnalysis' => $damageAnalysis?->only(['id', 'primary_finding', 'detailed_analysis', 'recommendations', 'priority', 'confidence_score', 'created_at']),
            'incidentAnalysis' => $incidentAnalysis?->only(['id', 'primary_finding', 'detailed_analysis', 'priority', 'created_at']),
            'runDamageAssessmentUrl' => route('fleet.incidents.run-damage-assessment', $incident),
            'runIncidentAnalysisUrl' => route('fleet.incidents.run-incident-analysis', $incident),
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            ...$this->enumOptions(),
        ]);
    }

    public function edit(Incident $incident): Response
    {
        $this->authorize('update', $incident);
        $incident->loadMedia('photos');
        $mediaItems = $incident->getMedia('photos')->map(fn ($m): array => [
            'id' => $m->id,
            'url' => $m->getUrl(),
            'mime_type' => $m->mime_type ?? '',
            'file_name' => $m->file_name ?? 'file',
        ])->values()->all();

        return Inertia::render('Fleet/Incidents/Edit', [
            'incident' => $incident,
            'mediaItems' => $mediaItems,
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            ...$this->enumOptions(),
        ]);
    }

    public function update(UpdateIncidentRequest $request, Incident $incident): RedirectResponse
    {
        $this->authorize('update', $incident);
        $validated = $request->validated();
        unset($validated['photos']);
        $validated['incident_timestamp'] = \Illuminate\Support\Facades\Date::parse($validated['incident_date'].' '.$validated['incident_time']);
        $incident->update($validated);
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $incident->addMedia($file)->toMediaCollection('photos');
            }
            dispatch(new RunDamageAssessmentJob('incident', $incident->id, $request->user()?->id));
        }
        if (! in_array(mb_trim((string) ($validated['description'] ?? '')), ['', '0'], true)) {
            dispatch(new RunIncidentAnalysisJob($incident->id, $request->user()?->id));
        }

        return to_route('fleet.incidents.show', $incident)->with('flash', ['status' => 'success', 'message' => 'Incident updated.']);
    }

    public function destroy(Incident $incident): RedirectResponse
    {
        $this->authorize('delete', $incident);
        $incident->delete();

        return to_route('fleet.incidents.index')->with('flash', ['status' => 'success', 'message' => 'Incident deleted.']);
    }

    /**
     * Run AI damage assessment on the incident's first photo. Queued; returns immediately.
     */
    public function runDamageAssessment(Request $request, Incident $incident): JsonResponse
    {
        $this->authorize('update', $incident);
        $incident->loadMedia('photos');
        if (! $incident->getFirstMedia('photos') instanceof \Spatie\MediaLibrary\MediaCollections\Models\Media) {
            return response()->json(['message' => 'No photo to analyze.', 'result_id' => null], 422);
        }
        dispatch(new RunDamageAssessmentJob('incident', $incident->id, $request->user()?->id));

        return response()->json([
            'message' => 'Damage analysis queued. Results will appear in AI Analysis and on this incident once complete.',
            'result_id' => null,
        ]);
    }

    /**
     * Run AI incident NLP analysis on the incident description and witness text. Queued.
     */
    public function runIncidentAnalysis(Request $request, Incident $incident): JsonResponse
    {
        $this->authorize('update', $incident);
        $desc = $incident->description ?? '';
        if (mb_trim($desc) === '') {
            return response()->json(['message' => 'No description to analyze.'], 422);
        }
        dispatch(new RunIncidentAnalysisJob($incident->id, $request->user()?->id));

        return response()->json([
            'message' => 'Incident analysis queued. Results will appear shortly.',
        ]);
    }

    private function enumOptions(): array
    {
        return [
            'incidentTypes' => array_map(fn (\App\Enums\Fleet\IncidentType $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\IncidentType::cases()),
            'severities' => array_map(fn (\App\Enums\Fleet\IncidentSeverity $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\IncidentSeverity::cases()),
            'statuses' => array_map(fn (\App\Enums\Fleet\IncidentStatus $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\IncidentStatus::cases()),
            'faultDeterminations' => array_map(fn (\App\Enums\Fleet\FaultDetermination $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\FaultDetermination::cases()),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreIncidentRequest;
use App\Http\Requests\Fleet\UpdateIncidentRequest;
use App\Models\Fleet\Driver;
use App\Models\Fleet\Incident;
use App\Models\Fleet\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class IncidentController extends Controller
{
    private function enumOptions(): array
    {
        return [
            'incidentTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\IncidentType::cases()),
            'severities' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\IncidentSeverity::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\IncidentStatus::cases()),
            'faultDeterminations' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\FaultDetermination::cases()),
        ];
    }

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
        $validated['incident_timestamp'] = Carbon::parse($validated['incident_date'] . ' ' . $validated['incident_time']);
        $incident = Incident::create($validated);
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $incident->addMedia($file)->toMediaCollection('photos');
            }
        }
        return to_route('fleet.incidents.index')->with('flash', ['status' => 'success', 'message' => 'Incident created.']);
    }

    public function show(Incident $incident): Response
    {
        $this->authorize('view', $incident);
        $incident->load(['vehicle', 'driver', 'reportedByUser', 'investigatingOfficerUser']);
        $incident->loadMedia('photos');
        $mediaItems = $incident->getMedia('photos')->map(fn ($m) => [
            'id' => $m->id,
            'url' => $m->getUrl(),
            'mime_type' => $m->mime_type ?? '',
            'file_name' => $m->file_name ?? 'file',
        ])->values()->all();

        return Inertia::render('Fleet/Incidents/Show', [
            'incident' => $incident,
            'mediaItems' => $mediaItems,
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            ...$this->enumOptions(),
        ]);
    }

    public function edit(Incident $incident): Response
    {
        $this->authorize('update', $incident);
        $incident->loadMedia('photos');
        $mediaItems = $incident->getMedia('photos')->map(fn ($m) => [
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
        $validated['incident_timestamp'] = Carbon::parse($validated['incident_date'] . ' ' . $validated['incident_time']);
        $incident->update($validated);
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $file) {
                $incident->addMedia($file)->toMediaCollection('photos');
            }
        }
        return to_route('fleet.incidents.show', $incident)->with('flash', ['status' => 'success', 'message' => 'Incident updated.']);
    }

    public function destroy(Incident $incident): RedirectResponse
    {
        $this->authorize('delete', $incident);
        $incident->delete();
        return to_route('fleet.incidents.index')->with('flash', ['status' => 'success', 'message' => 'Incident deleted.']);
    }
}

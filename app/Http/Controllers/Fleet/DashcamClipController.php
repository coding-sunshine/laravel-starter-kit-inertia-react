<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\DashcamClipEventType;
use App\Enums\Fleet\DashcamClipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreDashcamClipRequest;
use App\Http\Requests\Fleet\UpdateDashcamClipRequest;
use App\Models\Fleet\DashcamClip;
use App\Models\Fleet\Driver;
use App\Models\Fleet\Incident;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DashcamClipController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DashcamClip::class);
        $clips = DashcamClip::query()
            ->with(['vehicle', 'driver', 'incident'])
            ->when($request->input('vehicle_id'), fn ($q, $v) => $q->where('vehicle_id', $v))
            ->when($request->input('driver_id'), fn ($q, $v) => $q->where('driver_id', $v))
            ->when($request->input('incident_id'), fn ($q, $v) => $q->where('incident_id', $v))
            ->latest('recorded_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/DashcamClips/Index', [
            'dashcamClips' => $clips,
            'filters' => $request->only(['vehicle_id', 'driver_id', 'incident_id']),
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]),
            'incidents' => Incident::query()->latest('incident_date')->limit(200)->get(['id', 'incident_number']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', DashcamClip::class);

        return Inertia::render('Fleet/DashcamClips/Create', [
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]),
            'incidents' => Incident::query()->latest('incident_date')->limit(200)->get(['id', 'incident_number']),
            'eventTypes' => array_map(fn (DashcamClipEventType $c): array => ['value' => $c->value, 'name' => $c->name], DashcamClipEventType::cases()),
            'statuses' => array_map(fn (DashcamClipStatus $c): array => ['value' => $c->value, 'name' => $c->name], DashcamClipStatus::cases()),
        ]);
    }

    public function store(StoreDashcamClipRequest $request): RedirectResponse
    {
        $this->authorize('create', DashcamClip::class);
        DashcamClip::query()->create($request->validated());

        return to_route('fleet.dashcam-clips.index')->with('flash', ['status' => 'success', 'message' => 'Dashcam clip created.']);
    }

    public function show(DashcamClip $dashcam_clip): Response
    {
        $this->authorize('view', $dashcam_clip);
        $dashcam_clip->load(['vehicle', 'driver', 'incident']);

        return Inertia::render('Fleet/DashcamClips/Show', ['dashcamClip' => $dashcam_clip]);
    }

    public function edit(DashcamClip $dashcam_clip): Response
    {
        $this->authorize('update', $dashcam_clip);

        return Inertia::render('Fleet/DashcamClips/Edit', [
            'dashcamClip' => $dashcam_clip,
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]),
            'incidents' => Incident::query()->latest('incident_date')->limit(200)->get(['id', 'incident_number']),
            'eventTypes' => array_map(fn (DashcamClipEventType $c): array => ['value' => $c->value, 'name' => $c->name], DashcamClipEventType::cases()),
            'statuses' => array_map(fn (DashcamClipStatus $c): array => ['value' => $c->value, 'name' => $c->name], DashcamClipStatus::cases()),
        ]);
    }

    public function update(UpdateDashcamClipRequest $request, DashcamClip $dashcam_clip): RedirectResponse
    {
        $this->authorize('update', $dashcam_clip);
        $dashcam_clip->update($request->validated());

        return to_route('fleet.dashcam-clips.show', $dashcam_clip)->with('flash', ['status' => 'success', 'message' => 'Dashcam clip updated.']);
    }

    public function destroy(DashcamClip $dashcam_clip): RedirectResponse
    {
        $this->authorize('delete', $dashcam_clip);
        $dashcam_clip->delete();

        return to_route('fleet.dashcam-clips.index')->with('flash', ['status' => 'success', 'message' => 'Dashcam clip deleted.']);
    }
}

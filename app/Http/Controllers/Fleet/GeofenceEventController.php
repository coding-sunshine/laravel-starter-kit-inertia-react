<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\GeofenceEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class GeofenceEventController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', GeofenceEvent::class);
        $events = GeofenceEvent::query()
            ->with(['geofence', 'vehicle', 'driver'])
            ->when($request->input('geofence_id'), fn ($q, $id) => $q->where('geofence_id', $id))
            ->when($request->input('event_type'), fn ($q, $type) => $q->where('event_type', $type))
            ->when($request->input('from_date'), fn ($q, $date) => $q->whereDate('occurred_at', '>=', $date))
            ->when($request->input('to_date'), fn ($q, $date) => $q->whereDate('occurred_at', '<=', $date))
            ->orderByDesc('occurred_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/GeofenceEvents/Index', [
            'geofenceEvents' => $events,
            'filters' => $request->only(['geofence_id', 'event_type', 'from_date', 'to_date']),
            'geofences' => \App\Models\Fleet\Geofence::query()->orderBy('name')->get(['id', 'name']),
            'eventTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\GeofenceEventType::cases()),
        ]);
    }
}

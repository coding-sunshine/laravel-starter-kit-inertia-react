<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\ElockEventType;
use App\Http\Controllers\Controller;
use App\Models\Fleet\ElockEvent;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ElockEventController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ElockEvent::class);
        $events = ElockEvent::query()
            ->with('vehicle')
            ->when($request->input('vehicle_id'), fn ($q, $id) => $q->where('vehicle_id', $id))
            ->when($request->input('from_date'), fn ($q, $date) => $q->whereDate('event_timestamp', '>=', $date))
            ->when($request->input('to_date'), fn ($q, $date) => $q->whereDate('event_timestamp', '<=', $date))
            ->when($request->input('event_type'), fn ($q, $type) => $q->where('event_type', $type))
            ->orderByDesc('event_timestamp')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/ElockEvents/Index', [
            'elockEvents' => $events,
            'filters' => $request->only(['vehicle_id', 'from_date', 'to_date', 'event_type']),
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'eventTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], ElockEventType::cases()),
        ]);
    }

    public function show(ElockEvent $e_lock_event): Response
    {
        $this->authorize('view', $e_lock_event);
        $e_lock_event->load('vehicle');

        return Inertia::render('Fleet/ElockEvents/Show', ['elockEvent' => $e_lock_event]);
    }
}

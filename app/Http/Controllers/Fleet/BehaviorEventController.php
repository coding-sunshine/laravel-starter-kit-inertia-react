<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\BehaviorEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BehaviorEventController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', BehaviorEvent::class);
        $events = BehaviorEvent::query()
            ->with(['vehicle', 'driver', 'trip'])
            ->when($request->input('vehicle_id'), fn ($q, $id) => $q->where('vehicle_id', $id))
            ->when($request->input('driver_id'), fn ($q, $id) => $q->where('driver_id', $id))
            ->when($request->input('event_type'), fn ($q, $type) => $q->where('event_type', $type))
            ->when($request->input('from_date'), fn ($q, $date) => $q->whereDate('occurred_at', '>=', $date))
            ->when($request->input('to_date'), fn ($q, $date) => $q->whereDate('occurred_at', '<=', $date))
            ->orderByDesc('occurred_at')
            ->paginate(15)
            ->withQueryString();

        $eventTypes = array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\BehaviorEventType::cases());

        return Inertia::render('Fleet/BehaviorEvents/Index', [
            'behaviorEvents' => $events,
            'filters' => $request->only(['vehicle_id', 'driver_id', 'event_type', 'from_date', 'to_date']),
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'eventTypes' => $eventTypes,
        ]);
    }

    public function show(BehaviorEvent $behavior_event): Response
    {
        $this->authorize('view', $behavior_event);
        $behavior_event->load(['vehicle', 'driver', 'trip']);

        return Inertia::render('Fleet/BehaviorEvents/Show', ['behaviorEvent' => $behavior_event]);
    }
}

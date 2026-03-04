<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\AxleLoadReading;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AxleLoadReadingController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AxleLoadReading::class);
        $readings = AxleLoadReading::query()
            ->with(['vehicle', 'trip'])
            ->when($request->input('vehicle_id'), fn ($q, $id) => $q->where('vehicle_id', $id))
            ->when($request->input('date'), fn ($q, $date) => $q->whereDate('recorded_at', $date))
            ->when($request->has('overload_flag') && $request->input('overload_flag') !== '', fn ($q) => $q->where('overload_flag', (bool) $request->input('overload_flag')))
            ->latest('recorded_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/AxleLoadReadings/Index', [
            'axleLoadReadings' => $readings,
            'filters' => $request->only(['vehicle_id', 'date', 'overload_flag']),
            'vehicles' => Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
        ]);
    }

    public function show(AxleLoadReading $axle_load_reading): Response
    {
        $this->authorize('view', $axle_load_reading);
        $axle_load_reading->load(['vehicle', 'trip']);

        return Inertia::render('Fleet/AxleLoadReadings/Show', ['axleLoadReading' => $axle_load_reading]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\TachographCalibrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreTachographCalibrationRequest;
use App\Http\Requests\Fleet\UpdateTachographCalibrationRequest;
use App\Models\Fleet\TelematicsDevice;
use App\Models\Fleet\TachographCalibration;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TachographCalibrationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', TachographCalibration::class);
        $calibrations = TachographCalibration::query()
            ->with(['vehicle', 'telematicsDevice'])
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('calibration_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/TachographCalibrations/Index', [
            'tachographCalibrations' => $calibrations,
            'filters' => $request->only(['status']),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], TachographCalibrationStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TachographCalibration::class);
        $vehicles = Vehicle::query()->orderBy('registration')->get(['id', 'registration'])->map(fn ($v) => ['id' => $v->id, 'name' => $v->registration]);
        $telematicsDevices = TelematicsDevice::query()->orderBy('device_id')->get(['id', 'device_id'])->map(fn ($t) => ['id' => $t->id, 'name' => $t->device_id]);

        return Inertia::render('Fleet/TachographCalibrations/Create', [
            'vehicles' => $vehicles,
            'telematicsDevices' => $telematicsDevices,
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], TachographCalibrationStatus::cases()),
        ]);
    }

    public function store(StoreTachographCalibrationRequest $request): RedirectResponse
    {
        $this->authorize('create', TachographCalibration::class);
        TachographCalibration::create($request->validated());
        return to_route('fleet.tachograph-calibrations.index')->with('flash', ['status' => 'success', 'message' => 'Tachograph calibration created.']);
    }

    public function show(TachographCalibration $tachograph_calibration): Response
    {
        $this->authorize('view', $tachograph_calibration);
        $tachograph_calibration->load(['vehicle', 'telematicsDevice']);
        return Inertia::render('Fleet/TachographCalibrations/Show', ['tachographCalibration' => $tachograph_calibration]);
    }

    public function edit(TachographCalibration $tachograph_calibration): Response
    {
        $this->authorize('update', $tachograph_calibration);
        $vehicles = Vehicle::query()->orderBy('registration')->get(['id', 'registration'])->map(fn ($v) => ['id' => $v->id, 'name' => $v->registration]);
        $telematicsDevices = TelematicsDevice::query()->orderBy('device_id')->get(['id', 'device_id'])->map(fn ($t) => ['id' => $t->id, 'name' => $t->device_id]);

        return Inertia::render('Fleet/TachographCalibrations/Edit', [
            'tachographCalibration' => $tachograph_calibration,
            'vehicles' => $vehicles,
            'telematicsDevices' => $telematicsDevices,
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], TachographCalibrationStatus::cases()),
        ]);
    }

    public function update(UpdateTachographCalibrationRequest $request, TachographCalibration $tachograph_calibration): RedirectResponse
    {
        $this->authorize('update', $tachograph_calibration);
        $tachograph_calibration->update($request->validated());
        return to_route('fleet.tachograph-calibrations.show', $tachograph_calibration)->with('flash', ['status' => 'success', 'message' => 'Tachograph calibration updated.']);
    }

    public function destroy(TachographCalibration $tachograph_calibration): RedirectResponse
    {
        $this->authorize('delete', $tachograph_calibration);
        $tachograph_calibration->delete();
        return to_route('fleet.tachograph-calibrations.index')->with('flash', ['status' => 'success', 'message' => 'Tachograph calibration deleted.']);
    }
}

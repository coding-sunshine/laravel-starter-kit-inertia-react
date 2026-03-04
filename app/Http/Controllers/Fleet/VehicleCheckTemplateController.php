<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Enums\Fleet\VehicleCheckTemplateCheckType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreVehicleCheckTemplateRequest;
use App\Http\Requests\Fleet\UpdateVehicleCheckTemplateRequest;
use App\Models\Fleet\VehicleCheckTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class VehicleCheckTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', VehicleCheckTemplate::class);
        $templates = VehicleCheckTemplate::query()
            ->when($request->input('check_type'), fn ($q, $v) => $q->where('check_type', $v))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/VehicleCheckTemplates/Index', [
            'vehicleCheckTemplates' => $templates,
            'filters' => $request->only(['check_type']),
            'checkTypes' => array_map(fn (VehicleCheckTemplateCheckType $c): array => ['value' => $c->value, 'name' => $c->name], VehicleCheckTemplateCheckType::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', VehicleCheckTemplate::class);

        return Inertia::render('Fleet/VehicleCheckTemplates/Create', [
            'checkTypes' => array_map(fn (VehicleCheckTemplateCheckType $c): array => ['value' => $c->value, 'name' => $c->name], VehicleCheckTemplateCheckType::cases()),
        ]);
    }

    public function store(StoreVehicleCheckTemplateRequest $request): RedirectResponse
    {
        $this->authorize('create', VehicleCheckTemplate::class);
        VehicleCheckTemplate::query()->create($request->validated());

        return to_route('fleet.vehicle-check-templates.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle check template created.']);
    }

    public function show(VehicleCheckTemplate $vehicle_check_template): Response
    {
        $this->authorize('view', $vehicle_check_template);

        return Inertia::render('Fleet/VehicleCheckTemplates/Show', ['vehicleCheckTemplate' => $vehicle_check_template]);
    }

    public function edit(VehicleCheckTemplate $vehicle_check_template): Response
    {
        $this->authorize('update', $vehicle_check_template);

        return Inertia::render('Fleet/VehicleCheckTemplates/Edit', [
            'vehicleCheckTemplate' => $vehicle_check_template,
            'checkTypes' => array_map(fn (VehicleCheckTemplateCheckType $c): array => ['value' => $c->value, 'name' => $c->name], VehicleCheckTemplateCheckType::cases()),
        ]);
    }

    public function update(UpdateVehicleCheckTemplateRequest $request, VehicleCheckTemplate $vehicle_check_template): RedirectResponse
    {
        $this->authorize('update', $vehicle_check_template);
        $vehicle_check_template->update($request->validated());

        return to_route('fleet.vehicle-check-templates.show', $vehicle_check_template)->with('flash', ['status' => 'success', 'message' => 'Vehicle check template updated.']);
    }

    public function destroy(VehicleCheckTemplate $vehicle_check_template): RedirectResponse
    {
        $this->authorize('delete', $vehicle_check_template);
        $vehicle_check_template->delete();

        return to_route('fleet.vehicle-check-templates.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle check template deleted.']);
    }
}

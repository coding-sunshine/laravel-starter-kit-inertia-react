<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreVehicleRequest;
use App\Http\Requests\Fleet\UpdateVehicleRequest;
use App\Models\Fleet\AiAnalysisResult;
use App\Models\Fleet\Defect;
use App\Models\Fleet\DriverVehicleAssignment;
use App\Models\Fleet\Trip;
use App\Models\Fleet\Vehicle;
use App\Models\Fleet\WorkOrder;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class VehicleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Vehicle::class);
        $query = Vehicle::query()
            ->with(['homeLocation', 'currentDriver'])
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->filled('odometer_min'), fn ($q) => $q->where('odometer_reading', '>=', (int) $request->input('odometer_min')))
            ->when($request->filled('odometer_max'), fn ($q) => $q->where('odometer_reading', '<=', (int) $request->input('odometer_max')))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%'.addcslashes((string) $request->input('search', ''), '%_').'%';
                $q->where(function ($q) use ($term): void {
                    $q->where('registration', 'like', $term)
                        ->orWhere('make', 'like', $term)
                        ->orWhere('model', 'like', $term);
                });
            })
            ->orderBy('registration');

        $vehicles = $query->paginate(15)->withQueryString();

        $summary = Inertia::defer(function () {
            $total = Vehicle::query()->count();
            $active = Vehicle::query()->where('status', 'active')->count();
            $inMaintenance = Vehicle::query()->where('status', 'maintenance')->count();
            $inactive = Vehicle::query()->where('status', 'inactive')->count();
            $dueForService = \App\Models\Fleet\ServiceSchedule::query()
                ->where('next_service_due_date', '<=', now()->addDays(14))
                ->distinct('vehicle_id')
                ->count('vehicle_id');

            return [
                'total' => $total,
                'active' => $active,
                'in_maintenance' => $inMaintenance,
                'inactive' => $inactive,
                'due_for_service' => $dueForService,
            ];
        }, 'summary');

        $vehicleIds = collect($vehicles->items())->pluck('id')->all();

        $aiInsights = Inertia::defer(function () use ($vehicleIds): array {
            return AiAnalysisResult::query()
                ->where('entity_type', 'vehicle')
                ->whereIn('entity_id', $vehicleIds)
                ->orderByDesc('created_at')
                ->get()
                ->unique('entity_id')
                ->mapWithKeys(fn (AiAnalysisResult $r) => [
                    $r->entity_id => [
                        'id' => $r->id,
                        'primary_finding' => $r->primary_finding,
                        'priority' => $r->priority,
                        'analysis_type' => $r->analysis_type,
                    ],
                ])
                ->all();
        }, 'aiInsights');

        return Inertia::render('Fleet/Vehicles/Index', [
            'vehicles' => $vehicles,
            'filters' => $request->only(['status', 'odometer_min', 'odometer_max', 'search']),
            'summary' => $summary,
            'aiInsights' => $aiInsights,
        ]);
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Vehicle::class);
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'min:1']]);
        $ids = array_values(array_unique($request->input('ids', [])));
        $deleted = 0;
        foreach ($ids as $id) {
            $vehicle = Vehicle::query()->find($id);
            if ($vehicle !== null && $request->user()->can('delete', $vehicle)) {
                $vehicle->delete();
                $deleted++;
            }
        }

        return to_route('fleet.vehicles.index')
            ->with('flash', ['status' => 'success', 'message' => "{$deleted} vehicle(s) deleted."]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Vehicle::class);
        $vehicles = Vehicle::query()
            ->with('currentDriver')
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->filled('odometer_min'), fn ($q) => $q->where('odometer_reading', '>=', (int) $request->input('odometer_min')))
            ->when($request->filled('odometer_max'), fn ($q) => $q->where('odometer_reading', '<=', (int) $request->input('odometer_max')))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = '%'.addcslashes((string) $request->input('search', ''), '%_').'%';
                $q->where(function ($q) use ($term): void {
                    $q->where('registration', 'like', $term)
                        ->orWhere('make', 'like', $term)
                        ->orWhere('model', 'like', $term);
                });
            })
            ->orderBy('registration')
            ->get();

        $filename = 'vehicles-'.now()->format('Y-m-d').'.csv';

        return ResponseFacade::streamDownload(
            function () use ($vehicles): void {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Registration', 'Make', 'Model', 'Status', 'Fuel Type', 'Current Driver'], escape: '\\');
                foreach ($vehicles as $v) {
                    $driver = $v->currentDriver ? $v->currentDriver->first_name.' '.$v->currentDriver->last_name : '';
                    fputcsv($out, [
                        $v->registration,
                        $v->make,
                        $v->model,
                        $v->status,
                        $v->fuel_type ?? '',
                        $driver,
                    ],
                        escape: '\\');
                }
                fclose($out);
            },
            $filename,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ],
        );
    }

    public function create(): Response
    {
        $this->authorize('create', Vehicle::class);
        $enum = fn ($cases): array => array_map(fn ($c): array => ['value' => $c->value, 'name' => $c->name], $cases);

        return Inertia::render('Fleet/Vehicles/Create', [
            'fuelTypes' => $enum(\App\Enums\Fleet\VehicleFuelType::cases()),
            'vehicleTypes' => $enum(\App\Enums\Fleet\VehicleType::cases()),
            'statuses' => $enum(\App\Enums\Fleet\VehicleStatus::cases()),
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        Vehicle::query()->create($request->validated());

        return to_route('fleet.vehicles.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle created.']);
    }

    public function show(Vehicle $vehicle): Response
    {
        $this->authorize('view', $vehicle);
        $vehicle->load(['homeLocation', 'currentDriver', 'driverAssignments' => fn ($q) => $q->with('driver')->orderByDesc('assigned_date')]);

        $recentWorkOrders = WorkOrder::query()
            ->where('vehicle_id', $vehicle->id)->latest()
            ->limit(5)
            ->get(['id', 'work_order_number', 'title', 'status', 'created_at']);
        $recentDefects = Defect::query()
            ->where('vehicle_id', $vehicle->id)
            ->latest('reported_at')
            ->limit(5)
            ->get(['id', 'defect_number', 'title', 'severity', 'reported_at']);
        $recentTrips = Trip::query()
            ->where('vehicle_id', $vehicle->id)
            ->latest('started_at')
            ->limit(5)
            ->get(['id', 'started_at', 'ended_at']);

        $aiInsight = AiAnalysisResult::query()
            ->where('entity_type', 'vehicle')
            ->where('entity_id', $vehicle->id)
            ->orderByDesc('created_at')
            ->first(['id', 'primary_finding', 'priority', 'analysis_type', 'recommendations']);

        return Inertia::render('Fleet/Vehicles/Show', [
            'vehicle' => $vehicle,
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
            'assignmentTypes' => array_map(fn (\App\Enums\Fleet\AssignmentType $c): array => ['name' => $c->name, 'value' => $c->value], \App\Enums\Fleet\AssignmentType::cases()),
            'recentWorkOrders' => $recentWorkOrders,
            'recentDefects' => $recentDefects,
            'recentTrips' => $recentTrips,
            'aiInsight' => $aiInsight,
        ]);
    }

    public function assignDriver(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);
        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'assignment_type' => ['required', 'string', 'in:primary,secondary,temporary'],
            'assigned_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:65535'],
        ]);
        $orgId = TenantContext::id();

        DriverVehicleAssignment::query()
            ->where('organization_id', $orgId)
            ->where('is_current', true)
            ->where(function ($q) use ($vehicle, $validated): void {
                $q->where('vehicle_id', $vehicle->id)->orWhere('driver_id', $validated['driver_id']);
            })
            ->update(['is_current' => false, 'unassigned_date' => $validated['assigned_date']]);

        DriverVehicleAssignment::query()->create([
            'organization_id' => $orgId,
            'driver_id' => $validated['driver_id'],
            'vehicle_id' => $vehicle->id,
            'assignment_type' => $validated['assignment_type'],
            'assigned_date' => $validated['assigned_date'],
            'is_current' => true,
            'notes' => $validated['notes'] ?? null,
            'assigned_by' => $request->user()->id,
        ]);

        $vehicle->update(['current_driver_id' => $validated['driver_id']]);

        return to_route('fleet.vehicles.show', $vehicle)->with('flash', ['status' => 'success', 'message' => 'Driver assigned.']);
    }

    public function unassignDriver(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);
        $assignment = DriverVehicleAssignment::query()
            ->where('vehicle_id', $vehicle->id)
            ->where('is_current', true)
            ->first();
        if ($assignment) {
            $assignment->update(['is_current' => false, 'unassigned_date' => now()->toDateString()]);
        }
        $vehicle->update(['current_driver_id' => null]);

        return to_route('fleet.vehicles.show', $vehicle)->with('flash', ['status' => 'success', 'message' => 'Driver unassigned.']);
    }

    public function edit(Vehicle $vehicle): Response
    {
        $this->authorize('update', $vehicle);
        $enum = fn ($cases): array => array_map(fn ($c): array => ['value' => $c->value, 'name' => $c->name], $cases);

        return Inertia::render('Fleet/Vehicles/Edit', [
            'vehicle' => $vehicle,
            'fuelTypes' => $enum(\App\Enums\Fleet\VehicleFuelType::cases()),
            'vehicleTypes' => $enum(\App\Enums\Fleet\VehicleType::cases()),
            'statuses' => $enum(\App\Enums\Fleet\VehicleStatus::cases()),
            'locations' => \App\Models\Fleet\Location::query()->orderBy('name')->get(['id', 'name']),
            'drivers' => \App\Models\Fleet\Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);
        $vehicle->update($request->validated());

        return to_route('fleet.vehicles.show', $vehicle)->with('flash', ['status' => 'success', 'message' => 'Vehicle updated.']);
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('delete', $vehicle);
        $vehicle->delete();

        return to_route('fleet.vehicles.index')->with('flash', ['status' => 'success', 'message' => 'Vehicle deleted.']);
    }
}

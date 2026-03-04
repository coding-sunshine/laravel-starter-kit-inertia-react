<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreDriverRequest;
use App\Http\Requests\Fleet\UpdateDriverRequest;
use App\Models\Fleet\AiAnalysisResult;
use App\Models\Fleet\Driver;
use App\Models\Fleet\DriverVehicleAssignment;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFacade;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DriverController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Driver::class);
        $drivers = Driver::query()
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        $summary = Inertia::defer(function () {
            $total = Driver::query()->count();
            $active = Driver::query()->where('status', 'active')->count();
            $lowSafety = Driver::query()->where('safety_score', '<', 70)->count();
            $qualExpiring = \App\Models\Fleet\DriverQualification::query()
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', now()->addDays(30))
                ->where('expiry_date', '>=', now())
                ->count();

            return [
                'total' => $total,
                'active' => $active,
                'low_safety' => $lowSafety,
                'qualifications_expiring' => $qualExpiring,
            ];
        }, 'summary');

        $driverIds = collect($drivers->items())->pluck('id')->all();

        $aiInsights = Inertia::defer(function () use ($driverIds): array {
            return AiAnalysisResult::query()
                ->where('entity_type', 'driver')
                ->whereIn('entity_id', $driverIds)
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

        return Inertia::render('Fleet/Drivers/Index', [
            'drivers' => $drivers,
            'filters' => $request->only(['status']),
            'summary' => $summary,
            'aiInsights' => $aiInsights,
        ]);
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Driver::class);
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'min:1']]);
        $ids = array_values(array_unique($request->input('ids', [])));
        $deleted = 0;
        foreach ($ids as $id) {
            $driver = Driver::query()->find($id);
            if ($driver !== null && $request->user()->can('delete', $driver)) {
                $driver->delete();
                $deleted++;
            }
        }

        return to_route('fleet.drivers.index')
            ->with('flash', ['status' => 'success', 'message' => "{$deleted} driver(s) deleted."]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Driver::class);
        $drivers = Driver::query()
            ->with('currentAssignment.vehicle')
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('last_name')
            ->get();

        $filename = 'drivers-'.now()->format('Y-m-d').'.csv';

        return ResponseFacade::streamDownload(
            function () use ($drivers): void {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['First name', 'Last name', 'Email', 'Status', 'License number', 'License expiry', 'Current vehicle'], escape: '\\');
                foreach ($drivers as $d) {
                    $vehicle = $d->currentAssignment?->vehicle;
                    fputcsv($out, [
                        $d->first_name,
                        $d->last_name,
                        $d->email ?? '',
                        $d->status,
                        $d->license_number ?? '',
                        $d->license_expiry_date?->format('Y-m-d') ?? '',
                        $vehicle?->registration ?? '',
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
        $this->authorize('create', Driver::class);
        $enum = fn ($cases): array => array_map(fn ($c): array => ['value' => $c->value, 'name' => $c->name], $cases);

        return Inertia::render('Fleet/Drivers/Create', [
            'statuses' => $enum(\App\Enums\Fleet\DriverStatus::cases()),
            'licenseStatuses' => $enum(\App\Enums\Fleet\DriverLicenseStatus::cases()),
            'riskCategories' => $enum(\App\Enums\Fleet\DriverRiskCategory::cases()),
            'complianceStatuses' => $enum(\App\Enums\Fleet\DriverComplianceStatus::cases()),
        ]);
    }

    public function store(StoreDriverRequest $request): RedirectResponse
    {
        $this->authorize('create', Driver::class);
        Driver::query()->create($request->validated());

        return to_route('fleet.drivers.index')->with('flash', ['status' => 'success', 'message' => 'Driver created.']);
    }

    public function show(Driver $driver): Response
    {
        $this->authorize('view', $driver);
        $driver->load([
            'user',
            'vehicleAssignments' => fn ($q) => $q->with('vehicle')->orderByDesc('assigned_date'),
            'currentAssignment' => fn ($q) => $q->with('vehicle'),
        ]);

        return Inertia::render('Fleet/Drivers/Show', [
            'driver' => $driver,
            'vehicles' => \App\Models\Fleet\Vehicle::query()->orderBy('registration')->get(['id', 'registration']),
            'assignmentTypes' => array_map(fn (\App\Enums\Fleet\AssignmentType $c): array => ['name' => $c->name, 'value' => $c->value], \App\Enums\Fleet\AssignmentType::cases()),
        ]);
    }

    public function assignVehicle(Request $request, Driver $driver): RedirectResponse
    {
        $this->authorize('update', $driver);
        $validated = $request->validate([
            'vehicle_id' => ['required', 'integer', 'exists:vehicles,id'],
            'assignment_type' => ['required', 'string', 'in:primary,secondary,temporary'],
            'assigned_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:65535'],
        ]);
        $orgId = TenantContext::id();

        DriverVehicleAssignment::query()
            ->where('organization_id', $orgId)
            ->where('is_current', true)
            ->where(function ($q) use ($driver, $validated): void {
                $q->where('driver_id', $driver->id)->orWhere('vehicle_id', $validated['vehicle_id']);
            })
            ->update(['is_current' => false, 'unassigned_date' => $validated['assigned_date']]);

        DriverVehicleAssignment::query()->create([
            'organization_id' => $orgId,
            'driver_id' => $driver->id,
            'vehicle_id' => $validated['vehicle_id'],
            'assignment_type' => $validated['assignment_type'],
            'assigned_date' => $validated['assigned_date'],
            'is_current' => true,
            'notes' => $validated['notes'] ?? null,
            'assigned_by' => $request->user()->id,
        ]);

        \App\Models\Fleet\Vehicle::query()->withoutGlobalScopes()->where('id', $validated['vehicle_id'])->update([
            'current_driver_id' => $driver->id,
        ]);

        return to_route('fleet.drivers.show', $driver)->with('flash', ['status' => 'success', 'message' => 'Vehicle assigned.']);
    }

    public function unassignVehicle(Driver $driver): RedirectResponse
    {
        $this->authorize('update', $driver);
        $assignment = DriverVehicleAssignment::query()
            ->where('driver_id', $driver->id)
            ->where('is_current', true)
            ->first();
        if ($assignment) {
            $assignment->update(['is_current' => false, 'unassigned_date' => now()->toDateString()]);
            \App\Models\Fleet\Vehicle::query()->withoutGlobalScopes()->where('id', $assignment->vehicle_id)->update(['current_driver_id' => null]);
        }

        return to_route('fleet.drivers.show', $driver)->with('flash', ['status' => 'success', 'message' => 'Vehicle unassigned.']);
    }

    public function edit(Driver $driver): Response
    {
        $this->authorize('update', $driver);
        $enum = fn ($cases): array => array_map(fn ($c): array => ['value' => $c->value, 'name' => $c->name], $cases);

        return Inertia::render('Fleet/Drivers/Edit', [
            'driver' => $driver,
            'statuses' => $enum(\App\Enums\Fleet\DriverStatus::cases()),
            'licenseStatuses' => $enum(\App\Enums\Fleet\DriverLicenseStatus::cases()),
            'riskCategories' => $enum(\App\Enums\Fleet\DriverRiskCategory::cases()),
            'complianceStatuses' => $enum(\App\Enums\Fleet\DriverComplianceStatus::cases()),
        ]);
    }

    public function update(UpdateDriverRequest $request, Driver $driver): RedirectResponse
    {
        $this->authorize('update', $driver);
        $driver->update($request->validated());

        return to_route('fleet.drivers.show', $driver)->with('flash', ['status' => 'success', 'message' => 'Driver updated.']);
    }

    public function destroy(Driver $driver): RedirectResponse
    {
        $this->authorize('delete', $driver);
        $driver->delete();

        return to_route('fleet.drivers.index')->with('flash', ['status' => 'success', 'message' => 'Driver deleted.']);
    }
}

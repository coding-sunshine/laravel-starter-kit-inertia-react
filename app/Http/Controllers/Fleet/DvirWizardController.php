<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Actions\Fleet\SubmitDvirCheck;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreDvirCheckRequest;
use App\Models\Fleet\Driver;
use App\Models\Fleet\Vehicle;
use App\Models\Fleet\VehicleCheckTemplate;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class DvirWizardController extends Controller
{
    public function index(): Response
    {
        $this->authorize('create', \App\Models\Fleet\VehicleCheck::class);

        $vehicles = Vehicle::query()
            ->orderBy('registration')
            ->get(['id', 'registration'])
            ->map(fn ($v): array => ['id' => $v->id, 'name' => $v->registration]);

        $templates = VehicleCheckTemplate::query()
            ->where('is_active', true)
            ->whereIn('check_type', ['pre_trip', 'post_trip'])
            ->orderBy('check_type')
            ->orderBy('name')
            ->get(['id', 'name', 'check_type', 'checklist'])
            ->map(fn ($t): array => [
                'id' => $t->id,
                'name' => $t->name,
                'check_type' => $t->check_type,
                'checklist' => $t->checklist ?? [],
            ]);

        $drivers = Driver::query()
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn ($d): array => ['id' => $d->id, 'name' => $d->first_name.' '.$d->last_name]);

        return Inertia::render('Fleet/DvirWizard/Index', [
            'vehicles' => $vehicles,
            'vehicleCheckTemplates' => $templates,
            'drivers' => $drivers,
        ]);
    }

    public function store(StoreDvirCheckRequest $request, SubmitDvirCheck $action): RedirectResponse
    {
        $data = $request->validated();
        $check = $action->handle(
            vehicleId: (int) $data['vehicle_id'],
            vehicleCheckTemplateId: (int) $data['vehicle_check_template_id'],
            checkDate: $data['check_date'],
            performedByDriverId: isset($data['performed_by_driver_id']) ? (int) $data['performed_by_driver_id'] : null,
            performedByUserId: isset($data['performed_by_user_id']) ? (int) $data['performed_by_user_id'] : null,
            defectId: isset($data['defect_id']) ? (int) $data['defect_id'] : null,
            items: $data['items'],
        );

        return to_route('fleet.vehicle-checks.show', $check)
            ->with('flash', ['status' => 'success', 'message' => 'DVIR check submitted.']);
    }
}

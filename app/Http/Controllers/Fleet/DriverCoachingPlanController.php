<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreDriverCoachingPlanRequest;
use App\Http\Requests\Fleet\UpdateDriverCoachingPlanRequest;
use App\Models\Fleet\Driver;
use App\Models\Fleet\DriverCoachingPlan;
use App\Enums\Fleet\DriverCoachingPlanStatus;
use App\Enums\Fleet\DriverCoachingPlanType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class DriverCoachingPlanController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', DriverCoachingPlan::class);
        $plans = DriverCoachingPlan::query()
            ->with(['driver', 'assignedCoach'])
            ->when($request->input('driver_id'), fn ($q, $v) => $q->where('driver_id', $v))
            ->when($request->input('status'), fn ($q, $v) => $q->where('status', $v))
            ->orderByDesc('due_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/DriverCoachingPlans/Index', [
            'driverCoachingPlans' => $plans,
            'filters' => $request->only(['driver_id', 'status']),
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])
                ->map(fn ($d) => ['id' => $d->id, 'name' => $d->first_name . ' ' . $d->last_name]),
            'planTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], DriverCoachingPlanType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], DriverCoachingPlanStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', DriverCoachingPlan::class);
        return Inertia::render('Fleet/DriverCoachingPlans/Create', [
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])
                ->map(fn ($d) => ['id' => $d->id, 'name' => $d->first_name . ' ' . $d->last_name]),
            'planTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], DriverCoachingPlanType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], DriverCoachingPlanStatus::cases()),
        ]);
    }

    public function store(StoreDriverCoachingPlanRequest $request): RedirectResponse
    {
        $this->authorize('create', DriverCoachingPlan::class);
        DriverCoachingPlan::create($request->validated());
        return to_route('fleet.driver-coaching-plans.index')->with('flash', ['status' => 'success', 'message' => 'Coaching plan created.']);
    }

    public function show(DriverCoachingPlan $driver_coaching_plan): Response
    {
        $this->authorize('view', $driver_coaching_plan);
        $driver_coaching_plan->load(['driver', 'assignedCoach']);
        return Inertia::render('Fleet/DriverCoachingPlans/Show', ['driverCoachingPlan' => $driver_coaching_plan]);
    }

    public function edit(DriverCoachingPlan $driver_coaching_plan): Response
    {
        $this->authorize('update', $driver_coaching_plan);
        return Inertia::render('Fleet/DriverCoachingPlans/Edit', [
            'driverCoachingPlan' => $driver_coaching_plan,
            'drivers' => Driver::query()->orderBy('last_name')->get(['id', 'first_name', 'last_name'])
                ->map(fn ($d) => ['id' => $d->id, 'name' => $d->first_name . ' ' . $d->last_name]),
            'planTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], DriverCoachingPlanType::cases()),
            'statuses' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], DriverCoachingPlanStatus::cases()),
        ]);
    }

    public function update(UpdateDriverCoachingPlanRequest $request, DriverCoachingPlan $driver_coaching_plan): RedirectResponse
    {
        $this->authorize('update', $driver_coaching_plan);
        $driver_coaching_plan->update($request->validated());
        return to_route('fleet.driver-coaching-plans.show', $driver_coaching_plan)->with('flash', ['status' => 'success', 'message' => 'Coaching plan updated.']);
    }

    public function destroy(DriverCoachingPlan $driver_coaching_plan): RedirectResponse
    {
        $this->authorize('delete', $driver_coaching_plan);
        $driver_coaching_plan->delete();
        return to_route('fleet.driver-coaching-plans.index')->with('flash', ['status' => 'success', 'message' => 'Coaching plan deleted.']);
    }
}

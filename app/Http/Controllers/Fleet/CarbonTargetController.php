<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreCarbonTargetRequest;
use App\Http\Requests\Fleet\UpdateCarbonTargetRequest;
use App\Models\Fleet\CarbonTarget;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class CarbonTargetController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', CarbonTarget::class);
        $targets = CarbonTarget::query()
            ->orderBy('target_year')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/CarbonTargets/Index', [
            'carbonTargets' => $targets,
            'periods' => array_map(fn (\App\Enums\Fleet\CarbonTargetPeriod $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\CarbonTargetPeriod::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', CarbonTarget::class);

        return Inertia::render('Fleet/CarbonTargets/Create', [
            'periods' => array_map(fn (\App\Enums\Fleet\CarbonTargetPeriod $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\CarbonTargetPeriod::cases()),
        ]);
    }

    public function store(StoreCarbonTargetRequest $request): RedirectResponse
    {
        $this->authorize('create', CarbonTarget::class);
        CarbonTarget::query()->create($request->validated());

        return to_route('fleet.carbon-targets.index')->with('flash', ['status' => 'success', 'message' => 'Carbon target created.']);
    }

    public function show(CarbonTarget $carbon_target): Response
    {
        $this->authorize('view', $carbon_target);

        return Inertia::render('Fleet/CarbonTargets/Show', ['carbonTarget' => $carbon_target]);
    }

    public function edit(CarbonTarget $carbon_target): Response
    {
        $this->authorize('update', $carbon_target);

        return Inertia::render('Fleet/CarbonTargets/Edit', [
            'carbonTarget' => $carbon_target,
            'periods' => array_map(fn (\App\Enums\Fleet\CarbonTargetPeriod $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\CarbonTargetPeriod::cases()),
        ]);
    }

    public function update(UpdateCarbonTargetRequest $request, CarbonTarget $carbon_target): RedirectResponse
    {
        $this->authorize('update', $carbon_target);
        $carbon_target->update($request->validated());

        return to_route('fleet.carbon-targets.show', $carbon_target)->with('flash', ['status' => 'success', 'message' => 'Carbon target updated.']);
    }

    public function destroy(CarbonTarget $carbon_target): RedirectResponse
    {
        $this->authorize('delete', $carbon_target);
        $carbon_target->delete();

        return to_route('fleet.carbon-targets.index')->with('flash', ['status' => 'success', 'message' => 'Carbon target deleted.']);
    }
}

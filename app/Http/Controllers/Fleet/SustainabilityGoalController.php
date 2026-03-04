<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreSustainabilityGoalRequest;
use App\Http\Requests\Fleet\UpdateSustainabilityGoalRequest;
use App\Models\Fleet\SustainabilityGoal;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class SustainabilityGoalController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', SustainabilityGoal::class);
        $goals = SustainabilityGoal::query()->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/SustainabilityGoals/Index', [
            'sustainabilityGoals' => $goals,
            'statuses' => array_map(fn (\App\Enums\Fleet\SustainabilityGoalStatus $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\SustainabilityGoalStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', SustainabilityGoal::class);

        return Inertia::render('Fleet/SustainabilityGoals/Create', [
            'statuses' => array_map(fn (\App\Enums\Fleet\SustainabilityGoalStatus $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\SustainabilityGoalStatus::cases()),
        ]);
    }

    public function store(StoreSustainabilityGoalRequest $request): RedirectResponse
    {
        $this->authorize('create', SustainabilityGoal::class);
        SustainabilityGoal::query()->create($request->validated());

        return to_route('fleet.sustainability-goals.index')->with('flash', ['status' => 'success', 'message' => 'Sustainability goal created.']);
    }

    public function show(SustainabilityGoal $sustainability_goal): Response
    {
        $this->authorize('view', $sustainability_goal);

        return Inertia::render('Fleet/SustainabilityGoals/Show', ['sustainabilityGoal' => $sustainability_goal]);
    }

    public function edit(SustainabilityGoal $sustainability_goal): Response
    {
        $this->authorize('update', $sustainability_goal);

        return Inertia::render('Fleet/SustainabilityGoals/Edit', [
            'sustainabilityGoal' => $sustainability_goal,
            'statuses' => array_map(fn (\App\Enums\Fleet\SustainabilityGoalStatus $c): array => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\SustainabilityGoalStatus::cases()),
        ]);
    }

    public function update(UpdateSustainabilityGoalRequest $request, SustainabilityGoal $sustainability_goal): RedirectResponse
    {
        $this->authorize('update', $sustainability_goal);
        $sustainability_goal->update($request->validated());

        return to_route('fleet.sustainability-goals.show', $sustainability_goal)->with('flash', ['status' => 'success', 'message' => 'Sustainability goal updated.']);
    }

    public function destroy(SustainabilityGoal $sustainability_goal): RedirectResponse
    {
        $this->authorize('delete', $sustainability_goal);
        $sustainability_goal->delete();

        return to_route('fleet.sustainability-goals.index')->with('flash', ['status' => 'success', 'message' => 'Sustainability goal deleted.']);
    }
}

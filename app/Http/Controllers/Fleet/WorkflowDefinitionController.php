<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreWorkflowDefinitionRequest;
use App\Http\Requests\Fleet\UpdateWorkflowDefinitionRequest;
use App\Models\Fleet\WorkflowDefinition;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class WorkflowDefinitionController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', WorkflowDefinition::class);
        $definitions = WorkflowDefinition::query()
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/WorkflowDefinitions/Index', [
            'workflowDefinitions' => $definitions,
            'triggerTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkflowTriggerType::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', WorkflowDefinition::class);
        return Inertia::render('Fleet/WorkflowDefinitions/Create', [
            'triggerTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkflowTriggerType::cases()),
        ]);
    }

    public function store(StoreWorkflowDefinitionRequest $request): RedirectResponse
    {
        $this->authorize('create', WorkflowDefinition::class);
        $definition = WorkflowDefinition::create($request->validated());
        return to_route('fleet.workflow-definitions.index')->with('flash', ['status' => 'success', 'message' => 'Workflow definition created.']);
    }

    public function show(WorkflowDefinition $workflow_definition): Response
    {
        $this->authorize('view', $workflow_definition);
        return Inertia::render('Fleet/WorkflowDefinitions/Show', ['workflowDefinition' => $workflow_definition]);
    }

    public function edit(WorkflowDefinition $workflow_definition): Response
    {
        $this->authorize('update', $workflow_definition);
        return Inertia::render('Fleet/WorkflowDefinitions/Edit', [
            'workflowDefinition' => $workflow_definition,
            'triggerTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], \App\Enums\Fleet\WorkflowTriggerType::cases()),
        ]);
    }

    public function update(UpdateWorkflowDefinitionRequest $request, WorkflowDefinition $workflow_definition): RedirectResponse
    {
        $this->authorize('update', $workflow_definition);
        $workflow_definition->update($request->validated());
        return to_route('fleet.workflow-definitions.show', $workflow_definition)->with('flash', ['status' => 'success', 'message' => 'Workflow definition updated.']);
    }

    public function destroy(WorkflowDefinition $workflow_definition): RedirectResponse
    {
        $this->authorize('delete', $workflow_definition);
        $workflow_definition->delete();
        return to_route('fleet.workflow-definitions.index')->with('flash', ['status' => 'success', 'message' => 'Workflow definition deleted.']);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreWorkflowDefinitionRequest;
use App\Http\Requests\Fleet\UpdateWorkflowDefinitionRequest;
use App\Jobs\Fleet\RunWorkflowExecutionJob;
use App\Models\Fleet\WorkflowExecution;
use App\Models\Fleet\WorkflowDefinition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        return Inertia::render('Fleet/WorkflowDefinitions/Show', [
            'workflowDefinition' => $workflow_definition,
            'executeUrl' => route('fleet.workflow-definitions.execute', $workflow_definition),
        ]);
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

    public function execute(Request $request, WorkflowDefinition $workflow_definition): JsonResponse
    {
        $this->authorize('update', $workflow_definition);
        if (! $workflow_definition->is_active) {
            return response()->json(['message' => 'Workflow is not active.'], 422);
        }

        $execution = WorkflowExecution::create([
            'workflow_definition_id' => $workflow_definition->id,
            'started_at' => now(),
            'trigger_event' => 'manual',
            'trigger_entity_type' => null,
            'trigger_entity_id' => null,
            'trigger_data' => ['triggered_by' => $request->user()?->id],
            'status' => 'pending',
        ]);

        RunWorkflowExecutionJob::dispatch($execution->id);

        return response()->json([
            'message' => 'Workflow execution queued.',
            'workflow_execution_id' => $execution->id,
            'url' => route('fleet.workflow-executions.show', $execution),
        ]);
    }
}

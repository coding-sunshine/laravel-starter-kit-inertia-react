<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\WorkflowExecution;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class WorkflowExecutionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', WorkflowExecution::class);
        $executions = WorkflowExecution::query()
            ->with('workflowDefinition')
            ->when($request->input('workflow_definition_id'), fn ($q, $id) => $q->where('workflow_definition_id', $id))
            ->orderByDesc('started_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/WorkflowExecutions/Index', [
            'workflowExecutions' => $executions,
            'filters' => $request->only(['workflow_definition_id']),
        ]);
    }

    public function show(WorkflowExecution $workflow_execution): Response
    {
        $this->authorize('view', $workflow_execution);
        $workflow_execution->load('workflowDefinition');

        return Inertia::render('Fleet/WorkflowExecutions/Show', [
            'workflowExecution' => $workflow_execution,
        ]);
    }
}

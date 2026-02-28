<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Report;
use App\Models\Fleet\ReportExecution;
use App\Enums\Fleet\ReportExecutionStatus;
use App\Enums\Fleet\ReportTriggeredBy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ReportExecutionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ReportExecution::class);
        $executions = ReportExecution::query()
            ->with(['report', 'triggeredByUser'])
            ->when($request->input('report_id'), fn ($q, $v) => $q->where('report_id', $v))
            ->orderByDesc('execution_start')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/ReportExecutions/Index', [
            'reportExecutions' => $executions,
            'filters' => $request->only(['report_id']),
            'reports' => Report::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(ReportExecution $report_execution): Response
    {
        $this->authorize('view', $report_execution);
        $report_execution->load(['report', 'triggeredByUser']);

        return Inertia::render('Fleet/ReportExecutions/Show', ['reportExecution' => $report_execution]);
    }

    public function run(Request $request, Report $report): RedirectResponse
    {
        $this->authorize('view', $report);
        $execution = ReportExecution::create([
            'report_id' => $report->id,
            'execution_start' => now(),
            'status' => ReportExecutionStatus::Running,
            'triggered_by' => ReportTriggeredBy::Manual,
            'triggered_by_user_id' => $request->user()->id,
        ]);
        // In a full implementation you would dispatch a job to generate the report and update execution_end, status, file_path, etc.
        $execution->update([
            'execution_end' => now(),
            'status' => ReportExecutionStatus::Completed,
        ]);
        return to_route('fleet.report-executions.show', $execution)->with('flash', ['status' => 'success', 'message' => 'Report execution started.']);
    }
}

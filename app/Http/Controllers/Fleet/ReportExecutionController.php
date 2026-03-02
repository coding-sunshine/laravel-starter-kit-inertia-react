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
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $downloadAvailableIds = $executions->getCollection()
            ->filter(fn (ReportExecution $e) => $e->file_path && Storage::disk('local')->exists($e->file_path))
            ->pluck('id')
            ->all();

        return Inertia::render('Fleet/ReportExecutions/Index', [
            'reportExecutions' => $executions,
            'downloadAvailableIds' => $downloadAvailableIds,
            'filters' => $request->only(['report_id']),
            'reports' => Report::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(ReportExecution $report_execution): Response
    {
        $this->authorize('view', $report_execution);
        $report_execution->load(['report', 'triggeredByUser']);
        $downloadUrl = $report_execution->file_path && Storage::disk('local')->exists($report_execution->file_path)
            ? route('fleet.report-executions.download', $report_execution)
            : null;

        return Inertia::render('Fleet/ReportExecutions/Show', [
            'reportExecution' => $report_execution,
            'downloadUrl' => $downloadUrl,
        ]);
    }

    public function download(ReportExecution $report_execution): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $report_execution);
        if (! $report_execution->file_path || ! Storage::disk('local')->exists($report_execution->file_path)) {
            return redirect()->back()->with('flash', ['status' => 'error', 'message' => 'Report file is not available.']);
        }
        $filename = basename($report_execution->file_path);

        return Storage::disk('local')->download($report_execution->file_path, $filename);
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

        $ext = strtolower($report->format) === 'pdf' ? 'pdf' : 'csv';
        $filename = 'report-' . $report->id . '-exec-' . $execution->id . '-' . now()->format('Y-m-d-His') . '.' . $ext;
        $path = 'report-executions/' . $filename;

        if ($ext === 'csv') {
            $csv = "Report,Generated\n\"{$report->name}\"," . now()->toIso8601String() . "\n";
            Storage::disk('local')->put($path, $csv);
        } else {
            // Placeholder PDF (minimal text file for demo; real app would use Dompdf or similar)
            Storage::disk('local')->put($path, "%PDF-1.4\n1 0 obj\n<<\n/Type /Catalog\n/Pages 2 0 R\n>>\nendobj\n%%EOF");
        }

        $execution->update([
            'execution_end' => now(),
            'status' => ReportExecutionStatus::Completed,
            'file_path' => $path,
            'record_count' => 0,
        ]);

        return to_route('fleet.report-executions.show', $execution)->with('flash', ['status' => 'success', 'message' => 'Report run completed. You can view and download the result.']);
    }
}

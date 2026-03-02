<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreReportRequest;
use App\Http\Requests\Fleet\UpdateReportRequest;
use App\Models\Fleet\Report;
use App\Enums\Fleet\ReportFormat;
use App\Enums\Fleet\ReportScheduleFrequency;
use App\Enums\Fleet\ReportType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Report::class);
        $reports = Report::query()
            ->with(['reportExecutions' => fn ($q) => $q->orderByDesc('execution_start')->limit(1)])
            ->when($request->input('report_type'), fn ($q, $v) => $q->where('report_type', $v))
            ->when($request->input('schedule_frequency'), fn ($q, $v) => $q->where('schedule_frequency', $v))
            ->when($request->boolean('is_active'), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Fleet/Reports/Index', [
            'reports' => $reports,
            'filters' => $request->only(['report_type', 'schedule_frequency', 'is_active']),
            'reportTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], ReportType::cases()),
            'scheduleFrequencies' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], ReportScheduleFrequency::cases()),
            'formats' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], ReportFormat::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Report::class);
        return Inertia::render('Fleet/Reports/Create', [
            'reportTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], ReportType::cases()),
            'scheduleFrequencies' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], ReportScheduleFrequency::cases()),
            'formats' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], ReportFormat::cases()),
        ]);
    }

    public function store(StoreReportRequest $request): RedirectResponse
    {
        $this->authorize('create', Report::class);
        Report::create($request->validated());
        return to_route('fleet.reports.index')->with('flash', ['status' => 'success', 'message' => 'Report created.']);
    }

    public function show(Report $report): Response
    {
        $this->authorize('view', $report);
        $report->load([
            'reportExecutions' => fn ($q) => $q->orderByDesc('execution_start')->limit(10),
        ]);

        return Inertia::render('Fleet/Reports/Show', ['report' => $report]);
    }

    public function edit(Report $report): Response
    {
        $this->authorize('update', $report);
        return Inertia::render('Fleet/Reports/Edit', [
            'report' => $report,
            'reportTypes' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], ReportType::cases()),
            'scheduleFrequencies' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], ReportScheduleFrequency::cases()),
            'formats' => array_map(fn ($c) => ['value' => $c->value, 'name' => $c->name], ReportFormat::cases()),
        ]);
    }

    public function update(UpdateReportRequest $request, Report $report): RedirectResponse
    {
        $this->authorize('update', $report);
        $report->update($request->validated());
        return to_route('fleet.reports.show', $report)->with('flash', ['status' => 'success', 'message' => 'Report updated.']);
    }

    public function destroy(Report $report): RedirectResponse
    {
        $this->authorize('delete', $report);
        $report->delete();
        return to_route('fleet.reports.index')->with('flash', ['status' => 'success', 'message' => 'Report deleted.']);
    }
}

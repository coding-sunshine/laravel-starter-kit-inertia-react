<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreSidingPreIndentReportRequest;
use App\Http\Requests\UpdateSidingPreIndentReportRequest;
use App\Models\Siding;
use App\Models\SidingPreIndentReport;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class SidingPreIndentReportController extends Controller
{
    public function index(): InertiaResponse
    {
        $reports = SidingPreIndentReport::query()
            ->with('siding:id,name')
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('siding-pre-indent-reports/index', [
            'reports' => $reports,
        ]);
    }

    public function create(): InertiaResponse
    {
        return Inertia::render('siding-pre-indent-reports/create', [
            'sidings' => $this->sidingsForForm(),
        ]);
    }

    public function store(StoreSidingPreIndentReportRequest $request): RedirectResponse
    {
        $report = SidingPreIndentReport::query()->create($request->validated());

        return redirect()->route('siding-pre-indent-reports.show', $report);
    }

    public function show(SidingPreIndentReport $siding_pre_indent_report): InertiaResponse
    {
        $siding_pre_indent_report->load('siding:id,name');

        return Inertia::render('siding-pre-indent-reports/show', [
            'report' => $this->reportPayload($siding_pre_indent_report),
        ]);
    }

    public function edit(SidingPreIndentReport $siding_pre_indent_report): InertiaResponse
    {
        $siding_pre_indent_report->load('siding:id,name');

        return Inertia::render('siding-pre-indent-reports/edit', [
            'report' => $this->reportPayload($siding_pre_indent_report),
            'sidings' => $this->sidingsForForm(),
        ]);
    }

    public function update(UpdateSidingPreIndentReportRequest $request, SidingPreIndentReport $siding_pre_indent_report): RedirectResponse
    {
        $siding_pre_indent_report->update($request->validated());

        return redirect()->route('siding-pre-indent-reports.show', $siding_pre_indent_report);
    }

    public function destroy(SidingPreIndentReport $siding_pre_indent_report): RedirectResponse
    {
        $siding_pre_indent_report->delete();

        return redirect()->route('siding-pre-indent-reports.index')
            ->with('success', 'Report deleted.');
    }

    /**
     * @return EloquentCollection<int, Siding>
     */
    private function sidingsForForm(): EloquentCollection
    {
        return Siding::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return array<string, mixed>
     */
    private function reportPayload(SidingPreIndentReport $report): array
    {
        return [
            'id' => $report->id,
            'siding_id' => $report->siding_id,
            'report_date' => $report->report_date->toDateString(),
            'report_date_formatted' => $report->report_date->format('d.m.Y'),
            'total_indent_raised' => $report->total_indent_raised,
            'indent_available' => $report->indent_available,
            'loading_status_text' => $report->loading_status_text,
            'indent_details_text' => $report->indent_details_text,
            'heading_line' => $report->headingLine(),
            'siding' => $report->siding ? [
                'id' => $report->siding->id,
                'name' => $report->siding->name,
            ] : null,
        ];
    }
}

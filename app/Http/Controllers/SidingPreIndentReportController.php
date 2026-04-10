<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\IndexSidingPreIndentReportRequest;
use App\Http\Requests\StoreSidingPreIndentReportRequest;
use App\Http\Requests\UpdateSidingPreIndentReportRequest;
use App\Models\Siding;
use App\Models\SidingPreIndentReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class SidingPreIndentReportController extends Controller
{
    public function index(IndexSidingPreIndentReportRequest $request): InertiaResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $filters = $request->validated();

        $query = SidingPreIndentReport::query()
            ->with('siding:id,name');

        $this->scopeQueryToAccessibleSidings($query, $user);

        if (! empty($filters['siding_id'])) {
            $query->where('siding_id', $filters['siding_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('report_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('report_date', '<=', $filters['date_to']);
        }

        $reports = $query
            ->orderByDesc('report_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('siding-pre-indent-reports/index', [
            'reports' => $reports,
            'sidings' => $this->sidingsForForm($user),
            'filters' => [
                'siding_id' => $filters['siding_id'] ?? null,
                'date_from' => $filters['date_from'] ?? null,
                'date_to' => $filters['date_to'] ?? null,
            ],
        ]);
    }

    public function create(): InertiaResponse
    {
        /** @var User $user */
        $user = Auth::user();

        return Inertia::render('siding-pre-indent-reports/create', [
            'sidings' => $this->sidingsForForm($user),
        ]);
    }

    public function store(StoreSidingPreIndentReportRequest $request): RedirectResponse
    {
        $report = SidingPreIndentReport::query()->create($request->validated());

        return redirect()->route('siding-pre-indent-reports.show', $report);
    }

    public function show(SidingPreIndentReport $siding_pre_indent_report): InertiaResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authorizeReportAccess($user, $siding_pre_indent_report);

        $siding_pre_indent_report->load('siding:id,name');

        return Inertia::render('siding-pre-indent-reports/show', [
            'report' => $this->reportPayload($siding_pre_indent_report),
        ]);
    }

    public function edit(SidingPreIndentReport $siding_pre_indent_report): InertiaResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authorizeReportAccess($user, $siding_pre_indent_report);

        $siding_pre_indent_report->load('siding:id,name');

        return Inertia::render('siding-pre-indent-reports/edit', [
            'report' => $this->reportPayload($siding_pre_indent_report),
            'sidings' => $this->sidingsForForm($user),
        ]);
    }

    public function update(UpdateSidingPreIndentReportRequest $request, SidingPreIndentReport $siding_pre_indent_report): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authorizeReportAccess($user, $siding_pre_indent_report);

        $siding_pre_indent_report->update($request->validated());

        return redirect()->route('siding-pre-indent-reports.show', $siding_pre_indent_report);
    }

    public function destroy(SidingPreIndentReport $siding_pre_indent_report): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authorizeReportAccess($user, $siding_pre_indent_report);

        $siding_pre_indent_report->delete();

        return redirect()->route('siding-pre-indent-reports.index')
            ->with('success', 'Report deleted.');
    }

    /**
     * @param  EloquentBuilder<SidingPreIndentReport>  $query
     */
    private function scopeQueryToAccessibleSidings(EloquentBuilder $query, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $ids = $this->accessibleSidingIdsForUser($user);
        if ($ids === []) {
            $query->whereRaw('0 = 1');

            return;
        }

        $query->whereIn('siding_id', $ids);
    }

    /**
     * @return array<int>
     */
    private function accessibleSidingIdsForUser(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return Siding::query()->where('is_active', true)->pluck('id')->all();
        }

        return $user->accessibleSidings()->get()->pluck('id')->all();
    }

    /**
     * @return EloquentCollection<int, Siding>
     */
    private function sidingsForForm(User $user): EloquentCollection
    {
        $ids = $this->accessibleSidingIdsForUser($user);
        if ($ids === []) {
            return new EloquentCollection([]);
        }

        return Siding::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function authorizeReportAccess(User $user, SidingPreIndentReport $report): void
    {
        if ($user->can('bypass-permissions')) {
            return;
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        if ($report->siding_id === null) {
            return;
        }

        if (! $user->canAccessSiding($report->siding_id)) {
            abort(403);
        }
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

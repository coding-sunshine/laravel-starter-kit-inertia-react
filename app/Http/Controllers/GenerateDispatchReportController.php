<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateDispatchReport;
use App\Jobs\GenerateDispatchReportJob;
use App\Models\Siding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class GenerateDispatchReportController extends Controller
{
    public function __construct(
        private readonly GenerateDispatchReport $generateDispatchReport
    ) {}

    /**
     * Generate DPR from siding_vehicle_dispatches, merging daily_vehicle_entries when present.
     */
    public function generate(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();
        $filters = $request->input('_filters', []);
        $filters = is_array($filters) ? $filters : [];
        $mode = in_array($request->input('mode'), ['sync', 'queue'], true)
            ? (string) $request->input('mode')
            : 'sync';

        $query = array_filter([
            'tab' => 'dpr',
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'date' => $filters['date'] ?? null,
            'permit_no' => $filters['permit_no'] ?? null,
            'truck_regd_no' => $filters['truck_regd_no'] ?? null,
        ]);

        if ($mode === 'queue') {
            GenerateDispatchReportJob::dispatch($sidingIds, $filters);

            return redirect()
                ->route('vehicle-dispatch.index', $query)
                ->with('success', 'DPR generation queued successfully. Please refresh in a moment.');
        }

        $count = $this->generateDispatchReport->handle($sidingIds, $filters);

        if ($count === 0) {
            if (GenerateDispatchReport::filtersDefineDateWindow($filters)) {
                return redirect()
                    ->route('vehicle-dispatch.index', $query)
                    ->with('success', 'No vehicle dispatches for the selected period. Any existing DPR for that date range was cleared.');
            }

            return redirect()
                ->route('vehicle-dispatch.index', $query)
                ->with('error', 'No vehicle dispatches found for the selected date or filters. Add dispatch records or adjust filters, then try again.');
        }

        return redirect()
            ->route('vehicle-dispatch.index', $query)
            ->with('success', "DPR generated successfully. {$count} records processed.");
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateDispatchReport;
use App\Jobs\GenerateDispatchReportJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class GenerateDispatchReportController extends Controller
{
    public function __construct(
        private readonly GenerateDispatchReport $generateDispatchReport
    ) {}

    /**
     * Generate DPR from joined siding_vehicle_dispatches and daily_vehicle_entries.
     */
    public function generate(Request $request): RedirectResponse
    {
        $currentSiding = \App\Services\SidingContext::get();
        $sidingId = $currentSiding?->id;
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
            'shift' => $filters['shift'] ?? null,
            'permit_no' => $filters['permit_no'] ?? null,
            'truck_regd_no' => $filters['truck_regd_no'] ?? null,
        ]);

        if ($mode === 'queue') {
            GenerateDispatchReportJob::dispatch($sidingId, $filters);

            return redirect()
                ->route('vehicle-dispatch.index', $query)
                ->with('success', 'DPR generation queued successfully. Please refresh in a moment.');
        }

        $count = $this->generateDispatchReport->handle($sidingId, $filters);

        if ($count === 0) {
            return redirect()
                ->route('vehicle-dispatch.index', $query)
                ->with('error', 'No matching challan records found to generate DPR. Please check that pass_no values in vehicle dispatches match e_challan_no values in daily vehicle entries, and that siding_id values match between tables.');
        }

        return redirect()
            ->route('vehicle-dispatch.index', $query)
            ->with('success', "DPR generated successfully. {$count} records processed.");
    }
}

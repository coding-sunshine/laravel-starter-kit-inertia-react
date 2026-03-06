<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\GenerateDispatchReport;
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

        $count = $this->generateDispatchReport->handle($sidingId);

        $filters = $request->input('_filters', []);
        $query = array_filter([
            'tab' => 'dpr',
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
            'date' => $filters['date'] ?? null,
            'shift' => $filters['shift'] ?? null,
            'permit_no' => $filters['permit_no'] ?? null,
            'truck_regd_no' => $filters['truck_regd_no'] ?? null,
        ]);

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

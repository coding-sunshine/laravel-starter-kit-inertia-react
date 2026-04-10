<?php

declare(strict_types=1);

namespace App\Http\Controllers\Exports;

use App\Exports\CoalTransportReportExport;
use App\Http\Controllers\Controller;
use App\Models\Siding;
use App\Services\CoalTransportReport\CoalTransportReportDataBuilder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class CoalTransportReportExportController extends Controller
{
    public function __construct(
        private readonly CoalTransportReportDataBuilder $coalTransportReportDataBuilder,
    ) {}

    public function __invoke(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        $user = Auth::user();
        abort_unless($user !== null, 403);

        $canExport = $user->can('bypass-permissions')
            || $user->hasPermissionTo('sections.mines_dispatch_data.view')
            || (
                $user->hasPermissionTo('sections.dashboard.view')
                && $user->hasPermissionTo('dashboard.widgets.operations_coal_transport')
            );
        abort_unless($canExport, 403);

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $date = Carbon::parse($validated['date'], config('app.timezone'))->startOfDay();

        $payload = $this->coalTransportReportDataBuilder->buildExportData($sidingIds, $date);

        $filename = 'Coal_Transport_Report_'.$date->toDateString().'.xlsx';

        return Excel::download(new CoalTransportReportExport($payload), $filename);
    }
}

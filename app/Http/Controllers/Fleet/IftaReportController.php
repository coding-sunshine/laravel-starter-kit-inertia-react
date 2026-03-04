<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\FuelTransaction;
use App\Models\Fleet\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

final class IftaReportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FuelTransaction::class);

        $dateFrom = $request->input('date_from', now()->startOfQuarter()->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $totals = FuelTransaction::query()
            ->whereBetween('transaction_timestamp', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
            ->select([
                'vehicle_id',
                DB::raw('SUM(litres) as total_litres'),
                DB::raw('SUM(total_cost) as total_cost'),
                DB::raw('COUNT(*) as transaction_count'),
            ])
            ->groupBy('vehicle_id')
            ->get();

        $vehicleIds = $totals->pluck('vehicle_id')->unique()->filter()->values()->all();
        $vehicles = Vehicle::query()
            ->whereIn('id', $vehicleIds)
            ->get(['id', 'registration', 'make', 'model'])
            ->keyBy('id');

        $rows = $totals->map(fn ($row): array => [
            'vehicle_id' => $row->vehicle_id,
            'vehicle_registration' => $vehicles->get($row->vehicle_id)?->registration ?? '-',
            'vehicle_make_model' => mb_trim(($vehicles->get($row->vehicle_id)?->make ?? '').' '.($vehicles->get($row->vehicle_id)?->model ?? '')),
            'total_litres' => (float) $row->total_litres,
            'total_cost' => (float) $row->total_cost,
            'transaction_count' => (int) $row->transaction_count,
        ])->values()->all();

        return Inertia::render('Fleet/Reports/IftaReport', [
            'rows' => $rows,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }
}

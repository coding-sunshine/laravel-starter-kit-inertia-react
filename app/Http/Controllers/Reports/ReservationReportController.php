<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\PropertyReservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ReservationReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user() !== null, 403);

        $totalReservations = PropertyReservation::query()->count();

        $thisMonth = PropertyReservation::query()
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        $byProject = PropertyReservation::query()
            ->join('projects', 'property_reservations.project_id', '=', 'projects.id')
            ->selectRaw('projects.title as project_title, count(*) as count')
            ->groupBy('project_title')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'project_title');

        $averagePurchasePrice = PropertyReservation::query()->avg('purchase_price');

        return Inertia::render('reports/reservations', [
            'totalReservations' => $totalReservations,
            'thisMonth' => $thisMonth,
            'byProject' => $byProject,
            'averagePurchasePrice' => $averagePurchasePrice !== null ? round($averagePurchasePrice, 2) : 0,
        ]);
    }
}

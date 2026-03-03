<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SalesReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user() !== null, 403);

        $totalSales = Sale::query()->count();
        $totalCommsIn = Sale::query()->sum('comms_in_total');
        $totalCommsOut = Sale::query()->sum('comms_out_total');
        $profitMargin = $totalCommsIn > 0 ? round((($totalCommsIn - $totalCommsOut) / $totalCommsIn) * 100, 1) : 0;

        $sixMonthsAgo = Carbon::now()->subMonths(6)->startOfMonth();
        $salesByMonth = Sale::query()
            ->where('created_at', '>=', $sixMonthsAgo)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, count(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month');

        $topAgents = Sale::query()
            ->join('contacts', 'sales.client_contact_id', '=', 'contacts.id')
            ->selectRaw("CONCAT(contacts.first_name, ' ', contacts.last_name) as agent_name, SUM(sales.comms_in_total) as total_commission")
            ->groupBy('agent_name')
            ->orderByDesc('total_commission')
            ->limit(10)
            ->pluck('total_commission', 'agent_name');

        return Inertia::render('reports/sales', [
            'totalSales' => $totalSales,
            'totalCommsIn' => round($totalCommsIn, 2),
            'totalCommsOut' => round($totalCommsOut, 2),
            'profitMargin' => $profitMargin,
            'salesByMonth' => $salesByMonth,
            'topAgents' => $topAgents,
        ]);
    }
}

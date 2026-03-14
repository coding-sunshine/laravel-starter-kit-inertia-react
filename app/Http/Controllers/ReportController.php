<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTables\CommissionReportDataTable;
use App\DataTables\LoginHistoryDataTable;
use App\DataTables\ReservationReportDataTable;
use App\DataTables\SaleReportDataTable;
use App\DataTables\TaskReportDataTable;
use App\Models\Commission;
use App\Models\LoginEvent;
use App\Models\PropertyReservation;
use App\Models\Sale;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('reports/index', [
            'reports' => [
                [
                    'id' => 'reservations',
                    'title' => 'Reservation Report',
                    'description' => 'Track reservations by stage, agent, and project with date filters.',
                    'icon' => 'building',
                    'href' => '/reports/reservations',
                ],
                [
                    'id' => 'tasks',
                    'title' => 'Task Report',
                    'description' => 'Monitor task completion rates, overdue tasks, and agent workload.',
                    'icon' => 'check-square',
                    'href' => '/reports/tasks',
                ],
                [
                    'id' => 'sales',
                    'title' => 'Sales Report',
                    'description' => 'Analyze sales performance, pipeline value, and settlement rates.',
                    'icon' => 'trending-up',
                    'href' => '/reports/sales',
                ],
                [
                    'id' => 'commissions',
                    'title' => 'Commission Report',
                    'description' => 'Commission breakdown by type and agent with payment status.',
                    'icon' => 'dollar-sign',
                    'href' => '/reports/commissions',
                ],
                [
                    'id' => 'login-history',
                    'title' => 'Login History',
                    'description' => 'Audit user login events including IP address and device fingerprint.',
                    'icon' => 'log-in',
                    'href' => '/reports/login-history',
                ],
                [
                    'id' => 'same-device',
                    'title' => 'Same Device Detection',
                    'description' => 'Identify device fingerprints shared by multiple users (fraud detection).',
                    'icon' => 'shield',
                    'href' => '/reports/same-device',
                ],
            ],
        ]);
    }

    public function show(Request $request, string $type): Response
    {
        return match ($type) {
            'reservations' => $this->reservationsReport($request),
            'tasks' => $this->tasksReport($request),
            'sales' => $this->salesReport($request),
            'commissions' => $this->commissionsReport($request),
            'login-history' => $this->loginHistoryReport($request),
            'same-device' => $this->sameDeviceReport($request),
            default => abort(404),
        };
    }

    private function reservationsReport(Request $request): Response
    {
        $byStage = PropertyReservation::query()
            ->selectRaw('stage, COUNT(*) as count')
            ->groupBy('stage')
            ->pluck('count', 'stage')
            ->toArray();

        return Inertia::render('reports/show', array_merge(
            ReservationReportDataTable::inertiaProps($request),
            [
                'reportType' => 'reservations',
                'reportTitle' => 'Reservation Report',
                'chartData' => collect($byStage)->map(fn ($count, $stage) => ['name' => ucfirst($stage), 'value' => $count])->values()->all(),
                'chartType' => 'bar',
            ]
        ));
    }

    private function tasksReport(Request $request): Response
    {
        $byStatus = Task::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return Inertia::render('reports/show', array_merge(
            TaskReportDataTable::inertiaProps($request),
            [
                'reportType' => 'tasks',
                'reportTitle' => 'Task Report',
                'chartData' => collect($byStatus)->map(fn ($count, $status) => ['name' => ucfirst((string) $status), 'value' => $count])->values()->all(),
                'chartType' => 'bar',
            ]
        ));
    }

    private function salesReport(Request $request): Response
    {
        $byStatus = Sale::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return Inertia::render('reports/show', array_merge(
            SaleReportDataTable::inertiaProps($request),
            [
                'reportType' => 'sales',
                'reportTitle' => 'Sales Report',
                'chartData' => collect($byStatus)->map(fn ($count, $status) => ['name' => ucfirst((string) $status), 'value' => $count])->values()->all(),
                'chartType' => 'bar',
            ]
        ));
    }

    private function commissionsReport(Request $request): Response
    {
        $byType = Commission::query()
            ->selectRaw('commission_type, SUM(amount) as total')
            ->groupBy('commission_type')
            ->pluck('total', 'commission_type')
            ->toArray();

        return Inertia::render('reports/show', array_merge(
            CommissionReportDataTable::inertiaProps($request),
            [
                'reportType' => 'commissions',
                'reportTitle' => 'Commission Report',
                'chartData' => collect($byType)->map(fn ($total, $type) => ['name' => ucfirst((string) $type), 'value' => (float) $total])->values()->all(),
                'chartType' => 'bar',
            ]
        ));
    }

    private function loginHistoryReport(Request $request): Response
    {
        $byDay = LoginEvent::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return Inertia::render('reports/show', array_merge(
            LoginHistoryDataTable::inertiaProps($request),
            [
                'reportType' => 'login-history',
                'reportTitle' => 'Login History',
                'chartData' => collect($byDay)->map(fn ($count, $date) => ['name' => $date, 'value' => $count])->values()->all(),
                'chartType' => 'line',
            ]
        ));
    }

    private function sameDeviceReport(Request $request): Response
    {
        // Find device fingerprints shared by 2+ distinct users within 30 days
        $sharedDevices = LoginEvent::query()
            ->selectRaw('device_fingerprint, COUNT(DISTINCT user_id) as user_count, COUNT(*) as login_count, MAX(created_at) as last_seen')
            ->whereNotNull('device_fingerprint')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('device_fingerprint')
            ->having('user_count', '>=', 2)
            ->orderByDesc('user_count')
            ->get()
            ->map(fn ($row) => [
                'fingerprint_masked' => mb_substr((string) $row->device_fingerprint, 0, 8).'...',
                'user_count' => $row->user_count,
                'login_count' => $row->login_count,
                'last_seen' => $row->last_seen,
            ])
            ->toArray();

        return Inertia::render('reports/show', [
            'reportType' => 'same-device',
            'reportTitle' => 'Same Device Detection',
            'chartData' => [],
            'chartType' => 'bar',
            'tableData' => null,
            'searchableColumns' => [],
            'sharedDevices' => $sharedDevices,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class TaskReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user() !== null, 403);

        $byStatus = Task::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $overdueCount = Task::query()
            ->whereNull('completed_at')
            ->where('due_at', '<', Carbon::now())
            ->count();

        $totalTasks = Task::query()->count();
        $completedTasks = Task::query()->whereNotNull('completed_at')->count();
        $completionRate = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0;

        $createdThisMonth = Task::query()
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        return Inertia::render('reports/tasks', [
            'byStatus' => $byStatus,
            'overdueCount' => $overdueCount,
            'completionRate' => $completionRate,
            'createdThisMonth' => $createdThisMonth,
        ]);
    }
}

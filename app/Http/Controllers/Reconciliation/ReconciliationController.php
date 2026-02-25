<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reconciliation;

use App\DataTables\ReconciliationDataTable;
use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ReconciliationController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $pending = Rake::query()
            ->whereIn('siding_id', $sidingIds)
            ->whereDoesntHave('weighments')
            ->count();

        return Inertia::render('reconciliation/index', [
            'tableData' => ReconciliationDataTable::makeTable($request),
            'summary' => ['pending' => $pending],
            'sidings' => $sidings,
        ]);
    }

    public function show(Request $request, Rake $rake): Response
    {
        // $this->authorize('view', $rake);

        $points = resolve(ReconcileRakeAction::class)->handle($rake);
        $rake->load('siding:id,name,code');

        return Inertia::render('reconciliation/show', [
            'rake' => $rake,
            'points' => $points,
        ]);
    }
}

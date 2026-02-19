<?php

declare(strict_types=1);

namespace App\Http\Controllers\Reconciliation;

use App\Actions\ReconcileRakeAction;
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

        $query = Rake::query()
            ->with('siding:id,name,code')
            ->whereIn('siding_id', $sidingIds)
            ->whereHas('weighments')
            ->latest('loading_end_time');

        if ($request->filled('siding_id')) {
            $query->where('siding_id', $request->input('siding_id'));
        }

        $rakes = $query->paginate(15)->withQueryString();
        $reconcile = app(ReconcileRakeAction::class);
        $rows = [];
        foreach ($rakes->items() as $rake) {
            $points = $reconcile->handle($rake);
            $worst = collect($points)->max('status') === 'MAJOR_DIFF' ? 'MAJOR_DIFF' : (collect($points)->contains('status', 'MINOR_DIFF') ? 'MINOR_DIFF' : 'MATCH');
            $rows[] = [
                'rake' => $rake,
                'overall_status' => $worst,
                'points' => $points,
            ];
        }

        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $summary = [
            'pending' => Rake::query()->whereIn('siding_id', $sidingIds)->whereDoesntHave('weighments')->count(),
            'matched' => 0,
            'mismatched' => 0,
        ];
        foreach ($rows as $r) {
            if ($r['overall_status'] === 'MATCH') {
                $summary['matched']++;
            } elseif (in_array($r['overall_status'], ['MINOR_DIFF', 'MAJOR_DIFF'], true)) {
                $summary['mismatched']++;
            }
        }

        return Inertia::render('reconciliation/index', [
            'rakes' => $rakes,
            'rows' => $rows,
            'summary' => $summary,
            'sidings' => $sidings,
        ]);
    }

    public function show(Request $request, Rake $rake): Response
    {
        $this->authorize('view', $rake);

        $points = app(ReconcileRakeAction::class)->handle($rake);
        $rake->load('siding:id,name,code');

        return Inertia::render('reconciliation/show', [
            'rake' => $rake,
            'points' => $points,
        ]);
    }
}

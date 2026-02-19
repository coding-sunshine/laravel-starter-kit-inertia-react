<?php

declare(strict_types=1);

namespace App\Http\Controllers\RailwayReceipts;

use App\Http\Controllers\Controller;
use App\Models\Penalty;
use App\Models\Siding;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class PenaltyController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        $query = Penalty::query()
            ->with('rake.siding:id,name,code')
            ->whereHas('rake', fn ($q) => $q->whereIn('siding_id', $sidingIds))
            ->latest('penalty_date');

        if ($request->filled('siding_id')) {
            $query->whereHas('rake', fn ($q) => $q->where('siding_id', $request->input('siding_id')));
        }
        if ($request->filled('rake_id')) {
            $query->where('rake_id', $request->input('rake_id'));
        }
        if ($request->filled('status')) {
            $query->where('penalty_status', $request->input('status'));
        }

        $penalties = $query->paginate(15)->withQueryString();
        $sidings = Siding::query()
            ->whereIn('id', $sidingIds)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('penalties/index', [
            'penalties' => $penalties,
            'sidings' => $sidings,
            'demurrage_rate_per_mt_hour' => config('rrmcs.demurrage_rate_per_mt_hour', 50),
        ]);
    }
}

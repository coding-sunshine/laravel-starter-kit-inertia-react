<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\DataTables\RakeDataTable;
use App\Http\Controllers\Controller;
use App\Models\Rake;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class RakesController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('rakes/index', [
            'tableData' => RakeDataTable::makeTable($request),
        ]);
    }

    public function show(Request $request, Rake $rake): Response
    {
        $this->authorize('view', $rake);

        $rake->load([
            'siding:id,name,code',
            'siding.loaders:id,siding_id,loader_name,code',
            'wagons.loader:id,loader_name,code',
            'txr',
            'weighments',
            'guardInspection',
            'rrDocuments',
            'penalties',
        ]);

        $demurrageRemainingMinutes = null;
        if (
            $rake->state === 'loading'
            && $rake->loading_start_time
            && $rake->free_time_minutes !== null
        ) {
            $end = $rake->loading_start_time->copy()->addMinutes((int) $rake->free_time_minutes);
            $demurrageRemainingMinutes = max(0, (int) now()->diffInMinutes($end, false));
        }

        return Inertia::render('rakes/show', [
            'rake' => $rake,
            'demurrageRemainingMinutes' => $demurrageRemainingMinutes,
            'demurrage_rate_per_mt_hour' => config('rrmcs.demurrage_rate_per_mt_hour', 50),
        ]);
    }
}

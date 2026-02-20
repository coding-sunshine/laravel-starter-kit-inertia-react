<?php

declare(strict_types=1);

namespace App\Http\Controllers\Rakes;

use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Services\SidingContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class RakesController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sidingIds = SidingContext::activeSidingIds($user);

        $rakes = Rake::query()
            ->with('siding:id,code,name')
            ->whereIn('siding_id', $sidingIds)
            ->latest('loading_start_time')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('rakes/index', [
            'rakes' => $rakes,
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

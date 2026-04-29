<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sidings;

use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\SectionTimer;
use App\Models\Siding;
use Inertia\Inertia;
use Inertia\Response;

final class SidingMonitorController extends Controller
{
    public function show(Siding $siding): Response
    {
        $activeRake = Rake::query()
            ->where('siding_id', $siding->id)
            ->whereIn('state', ['loading', 'placed'])
            ->with([
                'wagonLoadings' => fn ($q) => $q->with('wagon'),
            ])
            ->latest('placement_time')
            ->first();

        $freeMinutes = SectionTimer::query()
            ->where('section_name', 'loading')
            ->value('free_minutes') ?? 300;

        return Inertia::render('sidings/monitor', [
            'siding' => $siding->only('id', 'name'),
            'rake' => $activeRake ? [
                'id' => $activeRake->id,
                'status' => $activeRake->state,
                'wagon_count' => $activeRake->wagon_count,
                'placement_time' => $activeRake->placement_time?->toIso8601String(),
                'loading_end_time' => $activeRake->loading_end_time?->toIso8601String(),
                'wagons_loaded' => $activeRake->wagonLoadings->where('weight_source', 'weighbridge')->count(),
            ] : null,
            'wagons' => $activeRake
                ? $activeRake->wagonLoadings->map(fn ($wl) => [
                    'id' => $wl->wagon_id,
                    'sequence' => $wl->wagon?->wagon_number,
                    'loadrite_weight_mt' => $wl->loadrite_weight_mt,
                    'loaded_quantity_mt' => $wl->loaded_quantity_mt,
                    'cc_capacity_mt' => $wl->cc_capacity_mt,
                    'weight_source' => $wl->weight_source,
                    'percentage' => $wl->cc_capacity_mt > 0
                        ? round((float) (($wl->loadrite_weight_mt ?? $wl->loaded_quantity_mt ?? 0) / $wl->cc_capacity_mt) * 100, 1)
                        : 0,
                    'loadrite_override' => $wl->loadrite_override,
                ])->values()
                : [],
            'free_minutes' => $freeMinutes,
            'loadrite_active' => true,
        ]);
    }
}

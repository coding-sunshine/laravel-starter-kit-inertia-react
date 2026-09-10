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
            ->whereIn('state', ['loading', 'placed', 'pending'])
            ->has('wagonLoadings')
            ->with([
                'wagonLoadings' => fn ($q) => $q->with('wagon:id,wagon_sequence,wagon_number,pcc_weight_mt'),
            ])
            ->latest('id')
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
                'wagons_loaded' => $activeRake->wagonLoadings->whereNotNull('loadrite_weight_mt')->count(),
            ] : null,
            'wagons' => $activeRake
                ? $activeRake->wagonLoadings->map(function ($wl) {
                    // Prefer wagons.pcc_weight_mt (legal capacity from wagon master)
                    // over wagon_loading.cc_capacity_mt which is often 0/stale.
                    $pccMt = $wl->wagon?->pcc_weight_mt !== null ? (float) $wl->wagon->pcc_weight_mt : 0.0;
                    $effectiveCc = $pccMt > 0 ? $pccMt : (float) $wl->cc_capacity_mt;
                    $displayWeight = (float) ($wl->loadrite_weight_mt ?? $wl->loaded_quantity_mt ?? 0);

                    return [
                        'id' => $wl->wagon_id,
                        'sequence' => $wl->wagon?->wagon_sequence,
                        'loadrite_weight_mt' => $wl->loadrite_weight_mt !== null ? (float) $wl->loadrite_weight_mt : null,
                        'loaded_quantity_mt' => $wl->loaded_quantity_mt !== null ? (float) $wl->loaded_quantity_mt : null,
                        'cc_capacity_mt' => $effectiveCc,
                        'weight_source' => $wl->weight_source,
                        'percentage' => $effectiveCc > 0
                            ? round(($displayWeight / $effectiveCc) * 100, 1)
                            : 0.0,
                        'loadrite_override' => (bool) $wl->loadrite_override,
                    ];
                })->values()
                : [],
            'free_minutes' => $freeMinutes,
            'loadrite_active' => true,
        ]);
    }
}

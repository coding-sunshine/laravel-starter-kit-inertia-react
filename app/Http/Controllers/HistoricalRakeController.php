<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class HistoricalRakeController extends Controller
{
    public function index(Request $request): InertiaResponse
    {
        $sidings = Siding::query()->orderBy('name')->get(['id', 'name']);
        $firstSidingId = $sidings->first()?->id;

        $sidingId = $request->has('siding_id')
            ? (int) $request->get('siding_id')
            : $firstSidingId;

        $query = Rake::query()
            ->with('siding')
            ->whereIn('data_source', ['historical_excel', 'historical_manual', 'historical_import'])
            ->when($sidingId !== null, fn ($q) => $q->where('siding_id', $sidingId))
            ->orderByDesc('id');
        // dd($query->limit(50)->get());
        $rakes = $query->paginate(250)->withQueryString()->through(function (Rake $rake): array {
            return [
                'id' => $rake->id,
                'siding_id' => $rake->siding_id,
                'siding_name' => $rake->siding?->name,
                'rake_number' => $rake->rake_number,
                'priority_number' => $rake->priority_number,
                'rr_number' => $rake->rr_number,
                'wagon_count' => $rake->wagon_count,
                'loaded_weight_mt' => $rake->loaded_weight_mt,
                'under_load_mt' => $rake->under_load_mt,
                'over_load_mt' => $rake->over_load_mt,
                'overload_wagon_count' => $rake->overload_wagon_count,
                'detention_hours' => $rake->detention_hours,
                'shunting_hours' => $rake->shunting_hours,
                'total_amount_rs' => $rake->total_amount_rs,
                'destination' => $rake->destination,
                'pakur_imwb_period' => $rake->pakur_imwb_period,
                'loading_date' => $rake->loading_date?->toDateString(),
                'data_source' => $rake->data_source,
                'remarks' => $rake->remarks,
            ];
        });

        return Inertia::render('historical/railway-siding/index', [
            'rakes' => $rakes,
            'sidings' => $sidings->values(),
            'sidingId' => $sidingId,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'siding_id' => 'required|exists:sidings,id',
            'loading_date' => 'nullable|date',
        ]);

        $loadingDate = $validated['loading_date'] ?? null;

        $rake = Rake::query()->create([
            'siding_id' => $validated['siding_id'],
            'loading_date' => $loadingDate,
            'data_source' => 'historical_manual',
            'state' => 'completed',
        ]);

        $rake->load('siding');

        return response()->json([
            'rake' => [
                'id' => $rake->id,
                'siding_id' => $rake->siding_id,
                'siding_name' => $rake->siding?->name,
                'rake_number' => $rake->rake_number,
                'priority_number' => $rake->priority_number,
                'rr_number' => $rake->rr_number,
                'wagon_count' => $rake->wagon_count,
                'loaded_weight_mt' => $rake->loaded_weight_mt,
                'under_load_mt' => $rake->under_load_mt,
                'over_load_mt' => $rake->over_load_mt,
                'overload_wagon_count' => $rake->overload_wagon_count,
                'detention_hours' => $rake->detention_hours,
                'shunting_hours' => $rake->shunting_hours,
                'total_amount_rs' => $rake->total_amount_rs,
                'destination' => $rake->destination,
                'pakur_imwb_period' => $rake->pakur_imwb_period,
                'loading_date' => $rake->loading_date?->toDateString(),
                'data_source' => $rake->data_source,
                'remarks' => $rake->remarks,
            ],
        ], 201);
    }

    public function update(Request $request, Rake $rake): JsonResponse
    {
        if (! in_array($rake->data_source, ['historical_excel', 'historical_manual', 'historical_import'], true)) {
            abort(403, 'Only historical rakes can be edited.');
        }

        $data = $request->validate([
            'rake_number' => 'nullable|numeric',
            'priority_number' => 'nullable|integer',
            'rr_number' => 'nullable|numeric',
            'wagon_count' => 'nullable|integer',
            'loaded_weight_mt' => 'nullable|numeric',
            'under_load_mt' => 'nullable|numeric',
            'over_load_mt' => 'nullable|numeric',
            'overload_wagon_count' => 'nullable|integer',
            'detention_hours' => 'nullable|numeric',
            'shunting_hours' => 'nullable|numeric',
            'total_amount_rs' => 'nullable|numeric',
            'destination' => 'nullable|string|max:255',
            'pakur_imwb_period' => 'nullable|string|max:255',
            'loading_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:65535',
        ]);

        $rake->fill($data);
        $rake->save();
        $rake->load('siding');

        return response()->json([
            'rake' => [
                'id' => $rake->id,
                'siding_id' => $rake->siding_id,
                'siding_name' => $rake->siding?->name,
                'rake_number' => $rake->rake_number,
                'priority_number' => $rake->priority_number,
                'rr_number' => $rake->rr_number,
                'wagon_count' => $rake->wagon_count,
                'loaded_weight_mt' => $rake->loaded_weight_mt,
                'under_load_mt' => $rake->under_load_mt,
                'over_load_mt' => $rake->over_load_mt,
                'overload_wagon_count' => $rake->overload_wagon_count,
                'detention_hours' => $rake->detention_hours,
                'shunting_hours' => $rake->shunting_hours,
                'total_amount_rs' => $rake->total_amount_rs,
                'destination' => $rake->destination,
                'pakur_imwb_period' => $rake->pakur_imwb_period,
                'loading_date' => $rake->loading_date?->toDateString(),
                'data_source' => $rake->data_source,
                'remarks' => $rake->remarks,
            ],
        ]);
    }

    public function destroy(Request $request, Rake $rake): JsonResponse
    {
        if (in_array($rake->data_source, ['historical_excel', 'historical_import'], true)) {
            return response()->json([
                'message' => 'Imported historical records cannot be deleted.',
            ], 422);
        }

        $id = $rake->id;
        $rake->delete();

        return response()->json([
            'deleted' => true,
            'id' => $id,
        ]);
    }
}

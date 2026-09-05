<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\HistoricalRakeExport;
use App\Models\Rake;
use App\Models\Siding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class HistoricalRakeController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request): InertiaResponse
    {
        $sidings = Siding::query()->orderBy('name')->get(['id', 'name']);
        $firstSidingId = $sidings->first()?->id;

        $filters = $this->validatedFilterInput($request);

        $sidingId = $filters['siding_id'] ?? $firstSidingId;
        if ($sidingId === null) {
            $sidingId = $firstSidingId;
        }

        $query = $this->historicalRakesQuery($sidingId, $filters);

        $rakes = $query->paginate(self::PER_PAGE)->withQueryString()->through(function (Rake $rake): array {
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
            'filters' => [
                'loading_date_from' => $filters['loading_date_from'] ?? '',
                'loading_date_to' => $filters['loading_date_to'] ?? '',
                'rake_number' => $filters['rake_number'] ?? '',
                'rr_number' => $filters['rr_number'] ?? '',
                'destination' => $filters['destination'] ?? '',
            ],
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $sidings = Siding::query()->orderBy('name')->get(['id', 'name']);
        $firstSidingId = $sidings->first()?->id;

        $filters = $this->validatedFilterInput($request);
        $sidingId = $filters['siding_id'] ?? $firstSidingId;
        if ($sidingId === null) {
            $sidingId = $firstSidingId;
        }

        $rakes = $this->historicalRakesQuery($sidingId, $filters)->get();

        $filename = 'Historical_Rakes_Siding_'.($sidingId ?? 'all').'_'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(new HistoricalRakeExport($rakes), $filename);
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

    /**
     * @return array{
     *     siding_id: int|null,
     *     loading_date_from: string|null,
     *     loading_date_to: string|null,
     *     rake_number: string|null,
     *     rr_number: string|null,
     *     destination: string|null
     * }
     */
    private function validatedFilterInput(Request $request): array
    {
        $validated = $request->validate([
            'siding_id' => ['nullable', 'integer', 'exists:sidings,id'],
            'loading_date_from' => ['nullable', 'date'],
            'loading_date_to' => ['nullable', 'date'],
            'rake_number' => ['nullable', 'string', 'max:100'],
            'rr_number' => ['nullable', 'string', 'max:50'],
            'destination' => ['nullable', 'string', 'max:255'],
        ]);

        return [
            'siding_id' => isset($validated['siding_id']) ? (int) $validated['siding_id'] : null,
            'loading_date_from' => $validated['loading_date_from'] ?? null,
            'loading_date_to' => $validated['loading_date_to'] ?? null,
            'rake_number' => isset($validated['rake_number']) && $validated['rake_number'] !== '' ? $validated['rake_number'] : null,
            'rr_number' => isset($validated['rr_number']) && $validated['rr_number'] !== '' ? $validated['rr_number'] : null,
            'destination' => isset($validated['destination']) && $validated['destination'] !== '' ? $validated['destination'] : null,
        ];
    }

    /**
     * @param  array{siding_id: int|null, loading_date_from: string|null, loading_date_to: string|null, rake_number: string|null, rr_number: string|null, destination: string|null}  $filters
     */
    private function historicalRakesQuery(?int $sidingId, array $filters): Builder
    {
        $query = Rake::query()
            ->with('siding')
            ->whereIn('data_source', ['historical_excel', 'historical_manual', 'historical_import'])
            ->when($sidingId !== null, fn (Builder $q) => $q->where('siding_id', $sidingId));

        if ($filters['loading_date_from'] !== null) {
            $query->where('loading_date', '>=', $filters['loading_date_from']);
        }

        if ($filters['loading_date_to'] !== null) {
            $query->where('loading_date', '<=', $filters['loading_date_to']);
        }

        if ($filters['rake_number'] !== null) {
            $query->where('rake_number', 'like', '%'.addcslashes($filters['rake_number'], '%_\\').'%');
        }

        if ($filters['rr_number'] !== null) {
            $query->where('rr_number', 'like', '%'.addcslashes($filters['rr_number'], '%_\\').'%');
        }

        if ($filters['destination'] !== null) {
            $query->where('destination', 'like', '%'.addcslashes($filters['destination'], '%_\\').'%');
        }

        return $query->orderByDesc('id');
    }
}

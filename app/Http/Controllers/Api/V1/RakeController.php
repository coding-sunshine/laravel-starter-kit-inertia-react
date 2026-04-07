<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DataTables\RakeDataTable;
use App\Exports\RakesExport;
use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\SectionTimer;
use App\Models\Siding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class RakeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $query = RakeDataTable::tableBaseQuery();

        if ($request->filled('from_date')) {
            $query->whereDate('placement_time', '>=', $request->date('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('placement_time', '<=', $request->date('to_date'));
        }

        if ($request->filled('siding_id')) {
            $query->where('siding_id', $request->integer('siding_id'));
        }

        if ($request->filled('state')) {
            $query->where('state', $request->string('state'));
        }

        if ($request->filled('rake_number')) {
            $search = $request->string('rake_number')->toString();
            $query->where('rake_number', 'like', "%{$search}%");
        }

        if ($request->filled('data_source')) {
            $query->where('data_source', $request->string('data_source'));
        }

        /** @var LengthAwarePaginator $paginator */
        $paginator = $query
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        $items = $paginator->getCollection()->map(function (Rake $rake): array {
            $dto = RakeDataTable::fromModel($rake);

            return [
                'id' => $dto->id,
                'rake_number' => $dto->rake_number,
                'rake_type' => $dto->rake_type,
                'wagon_count' => $dto->wagon_count,
                'state' => $dto->state,
                'placement_time' => $dto->placement_time,
                'dispatch_time' => $dto->dispatch_time,
                'siding_id' => $dto->siding_id,
                'siding_code' => $dto->siding_code,
                'siding_name' => $dto->siding_name,
                'destination' => $dto->destination,
                'data_source' => $dto->data_source,
                'rr_document_id' => $dto->rr_document_id,
                'pdf_download_url' => $dto->pdf_download_url,
                'workflow_has_pending' => $dto->workflow_has_pending,
                'workflow_steps' => $dto->workflow_steps,
                'loaded_weight_mt' => $rake->loaded_weight_mt,
                'under_load_mt' => $rake->under_load_mt,
                'over_load_mt' => $rake->over_load_mt,
            ];
        })->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, Rake $rake): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        abort_unless(in_array($rake->siding_id, $sidingIds, true), 403);

        $rake->load([
            'siding:id,name,code',
            'siding.loaders:id,siding_id,loader_name,code',
            'wagons',
            'rakeWeighments',
            'txr.wagonUnfitLogs.wagon:id,wagon_number,wagon_sequence,wagon_type',
            'wagonLoadings.wagon:id,wagon_number,wagon_sequence,wagon_type,pcc_weight_mt',
            'wagonLoadings.loader:id,loader_name,code',
            'guardInspections',
            'rrDocument',
            'penalties',
            'appliedPenalties.penaltyType',
            'appliedPenalties.wagon',
        ]);

        if ($rake->state !== 'completed' && $this->rakeWorkflowCoreComplete($rake)) {
            $rake->update(['state' => 'completed']);
        }

        $demurrageRemainingMinutes = null;

        if (
            $rake->state === 'loading'
            && $rake->placement_time
            && $rake->loading_free_minutes !== null
        ) {
            $end = $rake->placement_time->copy()->addMinutes((int) $rake->loading_free_minutes);
            $demurrageRemainingMinutes = max(0, (int) now()->diffInMinutes($end, false));
        }

        $wagonTypes = \App\Models\WagonType::query()
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'full_form',
                'typical_use',
                'loading_method',
                'carrying_capacity_min_mt',
                'carrying_capacity_max_mt',
                'gross_tare_weight_mt',
                'default_pcc_weight_mt',
            ]);

        $loadingSection = SectionTimer::query()
            ->where('section_name', 'loading')
            ->first();

        $rakeArray = $rake->toArray();
        $rakeArray['loading_warning_minutes'] = $loadingSection?->warning_minutes;
        $rakeArray['loading_section_free_minutes'] = $loadingSection?->free_minutes ?? 180;

        $rakeArray['weighments'] = collect($rake->rakeWeighments ?? [])
            ->filter(fn ($rw) => ! empty($rw->pdf_file_path))
            ->map(function ($rw): array {
                return [
                    'id' => $rw->id,
                    'weighment_time' => $rw->gross_weighment_datetime?->toIso8601String(),
                    'total_weight_mt' => $rw->total_net_weight_mt,
                    'status' => $rw->status,
                    'train_speed_kmph' => $rw->maximum_train_speed_kmph,
                    'attempt_no' => $rw->attempt_no,
                ];
            })
            ->values()
            ->all();

        if (array_key_exists('guard_inspections', $rakeArray)) {
            $rakeArray['guardInspections'] = $rakeArray['guard_inspections'];
        }

        if (array_key_exists('wagon_loadings', $rakeArray)) {
            $rakeArray['wagonLoadings'] = $rakeArray['wagon_loadings'];
        }

        $rakeArray['rrDocuments'] = collect($rake->rrDocuments ?? [])->map(static function ($doc): array {
            return [
                'id' => $doc->id,
                'rr_number' => $doc->rr_number,
                'rr_received_date' => $doc->rr_received_date?->toIso8601String() ?? '',
                'rr_weight_mt' => $doc->rr_weight_mt,
                'document_status' => $doc->document_status,
                'diverrt_destination_id' => $doc->diverrt_destination_id,
            ];
        })->values()->all();

        $rakeArray['diverrtDestinations'] = collect($rake->diverrtDestinations ?? [])->map(static function ($row): array {
            return [
                'id' => $row->id,
                'location' => $row->location,
            ];
        })->values()->all();

        unset($rakeArray['rr_document'], $rakeArray['rr_documents'], $rakeArray['diverrt_destinations']);

        if (array_key_exists('applied_penalties', $rakeArray)) {
            $rakeArray['appliedPenalties'] = $rakeArray['applied_penalties'];
        }

        return response()->json([
            'data' => [
                'rake' => $rakeArray,
                'wagonTypes' => $wagonTypes,
                'demurrageRemainingMinutes' => $demurrageRemainingMinutes,
                'demurrage_rate_per_mt_hour' => config('rrmcs.demurrage_rate_per_mt_hour', 50),
            ],
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $query = RakeDataTable::tableBaseQuery();

        if ($request->filled('from_date')) {
            $query->whereDate('placement_time', '>=', $request->date('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('placement_time', '<=', $request->date('to_date'));
        }

        if ($request->filled('siding_id')) {
            $query->where('siding_id', $request->integer('siding_id'));
        }

        if ($request->filled('state')) {
            $query->where('state', $request->string('state'));
        }

        if ($request->filled('rake_number')) {
            $search = $request->string('rake_number')->toString();
            $query->where('rake_number', 'like', "%{$search}%");
        }

        if ($request->filled('data_source')) {
            $query->where('data_source', $request->string('data_source'));
        }

        $rows = $query
            ->orderByDesc('id')
            ->get()
            ->map(function (Rake $rake): array {
                return [
                    $rake->rake_number,
                    $rake->siding?->code,
                    $rake->siding?->name,
                    $rake->loading_date?->toDateString(),
                    $rake->placement_time?->toDateTimeString(),
                    $rake->dispatch_time?->toDateTimeString(),
                    $rake->wagon_count,
                    $rake->loaded_weight_mt,
                    $rake->under_load_mt,
                    $rake->over_load_mt,
                    $rake->detention_hours,
                    $rake->shunting_hours,
                    $rake->total_amount_rs,
                    $rake->rr_number,
                    $rake->state,
                    $rake->data_source,
                    $rake->created_at?->toDateTimeString(),
                ];
            });

        $filename = 'rakes-export-'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new RakesExport($rows), $filename);
    }

    private function rakeWorkflowCoreComplete(Rake $rake): bool
    {
        if ($rake->txr?->status !== 'completed') {
            return false;
        }

        $fitWagons = $rake->wagons->filter(fn ($w) => ! $w->is_unfit);

        $loadedWagonIds = $rake->wagonLoadings
            ->filter(fn ($l) => (float) $l->loaded_quantity_mt > 0)
            ->pluck('wagon_id')
            ->flip();

        if ($fitWagons->isEmpty() || ! $fitWagons->every(fn ($w) => $loadedWagonIds->has($w->id))) {
            return false;
        }

        return $rake->rakeWeighments->isNotEmpty();
    }
}

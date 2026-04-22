<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\DataTables\RailwayReceiptsRakeDataTable;
use App\Http\Controllers\Controller;
use App\Models\Rake;
use App\Models\RrDocument;
use App\Models\Siding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\QueryBuilder;

final class RailwayReceiptApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $this->applyDefaultLoadingDateFilter($request);

        $query = RailwayReceiptsRakeDataTable::tableBaseQuery();
        $this->applySimpleFilters($request, $query);

        /** @var LengthAwarePaginator $paginator */
        $paginator = QueryBuilder::for($query, $request)
            ->allowedFilters(RailwayReceiptsRakeDataTable::tableAllowedFilters())
            ->allowedSorts(RailwayReceiptsRakeDataTable::tableAllowedSorts())
            ->defaultSort(RailwayReceiptsRakeDataTable::tableDefaultSort())
            ->paginate($request->integer('per_page', 15))
            ->withQueryString();

        $items = $paginator->getCollection()->map(static function (Rake $rake): array {
            $dto = RailwayReceiptsRakeDataTable::fromModel($rake);

            return [
                'id' => $dto->id,
                'rake_number' => $dto->rake_number,
                'rake_serial_number' => $dto->rake_serial_number,
                'loading_date' => $dto->loading_date,
                'siding_id' => $dto->siding_id,
                'siding_code' => $dto->siding_code,
                'siding_name' => $dto->siding_name,
                'destination' => $dto->destination,
                'has_diversion' => $dto->has_diversion,
                'rr_document_id' => $dto->rr_document_id,
                'rr_number' => $dto->rr_number,
                'rr_received_date' => $dto->rr_received_date,
                'rr_weight_mt' => $dto->rr_weight_mt,
                'document_status' => $dto->document_status,
                'has_discrepancy' => $dto->has_discrepancy,
                'discrepancy_details' => $dto->discrepancy_details,
                'fnr' => $dto->fnr,
                'from_station_code' => $dto->from_station_code,
                'to_station_code' => $dto->to_station_code,
                'freight_total' => $dto->freight_total,
                'distance_km' => $dto->distance_km,
                'commodity_code' => $dto->commodity_code,
                'commodity_description' => $dto->commodity_description,
                'invoice_number' => $dto->invoice_number,
                'invoice_date' => $dto->invoice_date,
                'rate' => $dto->rate,
                'document_class' => $dto->document_class,
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

    public function show(Request $request, RrDocument $rrDocument): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $rrDocument->load([
            'rake.siding',
            'rake.wagons',
            'rake.appliedPenalties.penaltyType',
            'rake.appliedPenalties.wagon',
            'rrCharges',
            'wagonSnapshots',
            'penaltySnapshots',
        ]);

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        if ($rrDocument->rake && ! in_array($rrDocument->rake->siding_id, $sidingIds, true)) {
            abort(403);
        }

        $rake = $rrDocument->rake;

        return response()->json([
            'data' => [
                'header' => [
                    'id' => $rrDocument->id,
                    'rake_id' => $rrDocument->rake_id,
                    'rr_number' => $rrDocument->rr_number,
                    'rr_received_date' => $rrDocument->rr_received_date,
                    'rr_weight_mt' => $rrDocument->rr_weight_mt,
                    'fnr' => $rrDocument->fnr,
                    'document_status' => $rrDocument->document_status,
                    'from_station_code' => $rrDocument->from_station_code,
                    'to_station_code' => $rrDocument->to_station_code,
                    'freight_total' => $rrDocument->freight_total,
                ],
                'rake' => $rake ? [
                    'id' => $rake->id,
                    'rake_number' => $rake->rake_number,
                    'siding' => $rake->siding ? [
                        'id' => $rake->siding->id,
                        'name' => $rake->siding->name,
                        'code' => $rake->siding->code,
                    ] : null,
                ] : null,
                'charges' => $rrDocument->rrCharges->map(static function ($charge): array {
                    return [
                        'id' => $charge->id,
                        'charge_code' => $charge->charge_code,
                        'charge_name' => $charge->charge_name,
                        'amount' => $charge->amount,
                    ];
                })->values(),
                'wagonSnapshots' => $rrDocument->wagonSnapshots->map(static function ($w): array {
                    return [
                        'id' => $w->id,
                        'wagon_sequence' => $w->wagon_sequence,
                        'wagon_number' => $w->wagon_number,
                        'wagon_type' => $w->wagon_type,
                        'pcc_weight_mt' => $w->pcc_weight_mt,
                        'loaded_weight_mt' => $w->loaded_weight_mt,
                        'permissible_weight_mt' => $w->permissible_weight_mt,
                        'overload_weight_mt' => $w->overload_weight_mt,
                    ];
                })->values(),
                'penaltySnapshots' => $rrDocument->penaltySnapshots->map(static function ($p): array {
                    return [
                        'id' => $p->id,
                        'penalty_code' => $p->penalty_code,
                        'amount' => $p->amount,
                        'meta' => $p->meta,
                    ];
                })->values(),
            ],
        ]);
    }

    public function download(Request $request, RrDocument $rrDocument)
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $rrDocument->load('rake.siding');

        $sidingIds = $user->isSuperAdmin()
            ? Siding::query()->pluck('id')->all()
            : $user->accessibleSidings()->get()->pluck('id')->all();

        if ($rrDocument->rake && ! in_array($rrDocument->rake->siding_id, $sidingIds, true)) {
            abort(403);
        }

        $media = $rrDocument->getFirstMedia('rr_pdf');

        if ($media === null) {
            return response()->json([
                'message' => 'No PDF attached to this Railway Receipt.',
            ], 404);
        }

        if (! Storage::disk($media->disk)->exists($media->getPathRelativeToRoot())) {
            return response()->json([
                'message' => 'No PDF attached to this Railway Receipt.',
            ], 404);
        }

        return $media;
    }

    private function applyDefaultLoadingDateFilter(Request $request): void
    {
        $filter = $request->input('filter', []);
        if (! is_array($filter)) {
            $filter = [];
        }

        if (array_key_exists('loading_date', $filter)) {
            return;
        }

        if ($request->filled('loading_date_start') || $request->filled('loading_date_end')) {
            return;
        }

        $filter['loading_date'] = 'eq:'.now()->toDateString();
        $request->merge(['filter' => $filter]);
    }

    private function applySimpleFilters(Request $request, \Illuminate\Database\Eloquent\Builder $query): void
    {
        /** @var array<string, mixed> $filterQuery */
        $filterQuery = $request->query('filter', []);
        $filterQuery = is_array($filterQuery) ? $filterQuery : [];

        if ($request->filled('rake_number') && ! array_key_exists('rake_number', $filterQuery)) {
            $query->where('rake_number', 'like', '%'.$request->string('rake_number')->toString().'%');
        }

        if (
            $request->filled('siding_id')
            && ! array_key_exists('siding_code', $filterQuery)
            && ! array_key_exists('siding', $filterQuery)
        ) {
            $query->where('siding_id', $request->integer('siding_id'));
        }

        if ($request->filled('destination') && ! array_key_exists('destination', $filterQuery)) {
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($request): void {
                $value = $request->string('destination')->toString();
                $q->where('destination', 'like', '%'.$value.'%')
                    ->orWhere('destination_code', 'like', '%'.$value.'%');
            });
        }

        if ($request->filled('rr_number') && ! array_key_exists('rr_number', $filterQuery)) {
            $search = $request->string('rr_number')->toString();
            $query->whereHas('rrDocument', static function (\Illuminate\Database\Eloquent\Builder $q) use ($search): void {
                $q->where('rr_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('loading_date_start')) {
            $query->whereDate('loading_date', '>=', $request->date('loading_date_start'));
        }

        if ($request->filled('loading_date_end')) {
            $query->whereDate('loading_date', '<=', $request->date('loading_date_end'));
        }
    }
}
